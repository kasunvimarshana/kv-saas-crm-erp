# Service Layer Architecture Requirements

When implementing the Service Layer, follow these guidelines to maintain separation of concerns and clean business logic.

## Overview

The Service Layer contains the application's business logic and use cases. It orchestrates operations between:
- Controllers (presentation layer)
- Repositories (data access layer)
- Domain entities and value objects
- External services and APIs

## Service Structure

### 1. Basic Service Pattern

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Events\CustomerCreated;

class CustomerService
{
    /**
     * Create a new service instance
     */
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Create a new customer
     */
    public function createCustomer(array $data): Customer
    {
        DB::beginTransaction();
        try {
            // Validate business rules
            $this->validateCustomerData($data);

            // Create customer
            $customer = $this->customerRepository->create($data);

            // Trigger domain events
            event(new CustomerCreated($customer));

            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update customer information
     */
    public function updateCustomer(string $id, array $data): Customer
    {
        DB::beginTransaction();
        try {
            $customer = $this->customerRepository->findById($id);

            if (!$customer) {
                throw new \Exception("Customer not found: {$id}");
            }

            // Business logic validation
            $this->validateCustomerUpdate($customer, $data);

            // Update customer
            $customer = $this->customerRepository->update($customer, $data);

            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate customer data against business rules
     */
    private function validateCustomerData(array $data): void
    {
        // Example business rule: Check for duplicate email
        if (isset($data['email'])) {
            $existing = $this->customerRepository->findByEmail($data['email']);
            if ($existing) {
                throw new \Exception('Email address already in use');
            }
        }

        // Additional business validations
    }

    /**
     * Validate customer update
     */
    private function validateCustomerUpdate(Customer $customer, array $data): void
    {
        // Business rule: Cannot change email if customer has active orders
        if (isset($data['email']) && $data['email'] !== $customer->email) {
            if ($customer->orders()->where('status', 'active')->exists()) {
                throw new \Exception('Cannot change email while customer has active orders');
            }
        }
    }
}
```

### 2. Complex Service with Multiple Dependencies

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Repositories\Contracts\SalesOrderRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\ProductRepositoryInterface;
use Modules\Sales\Events\OrderCreated;
use Modules\Sales\Events\OrderConfirmed;
use Modules\Sales\Exceptions\InsufficientStockException;

class OrderProcessingService
{
    public function __construct(
        private SalesOrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
        private InventoryService $inventoryService,
        private NotificationService $notificationService
    ) {}

    /**
     * Create and process a new sales order
     */
    public function createOrder(array $orderData): SalesOrder
    {
        DB::beginTransaction();
        try {
            // Validate business rules
            $this->validateOrderData($orderData);

            // Check inventory availability
            $this->checkInventoryAvailability($orderData['items']);

            // Create order
            $order = $this->orderRepository->create($orderData);

            // Process order lines
            foreach ($orderData['items'] as $item) {
                $this->processOrderLine($order, $item);
            }

            // Calculate totals
            $this->calculateOrderTotals($order);

            // Reserve inventory
            $this->inventoryService->reserveStock($order);

            // Trigger domain event
            event(new OrderCreated($order));

            // Send notification
            $this->notificationService->sendOrderConfirmation($order);

            DB::commit();

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'total' => $order->total
            ]);

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Confirm order and initiate fulfillment
     */
    public function confirmOrder(string $orderId): SalesOrder
    {
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->findById($orderId);

            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            if ($order->status !== 'draft') {
                throw new \Exception('Only draft orders can be confirmed');
            }

            // Validate payment status
            if ($order->payment_status !== 'paid') {
                throw new \Exception('Order must be paid before confirmation');
            }

            // Update order status
            $order = $this->orderRepository->update($order, ['status' => 'confirmed']);

            // Commit inventory reservation
            $this->inventoryService->commitReservation($order);

            // Trigger domain event
            event(new OrderConfirmed($order));

            DB::commit();
            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate order data against business rules
     */
    private function validateOrderData(array $data): void
    {
        if (empty($data['items'])) {
            throw new \Exception('Order must have at least one item');
        }

        // Additional validations
    }

    /**
     * Check if sufficient inventory is available
     */
    private function checkInventoryAvailability(array $items): void
    {
        foreach ($items as $item) {
            $product = $this->productRepository->findById($item['product_id']);

            if (!$product) {
                throw new \Exception("Product not found: {$item['product_id']}");
            }

            $available = $this->inventoryService->getAvailableQuantity($product);

            if ($available < $item['quantity']) {
                throw new InsufficientStockException(
                    "Insufficient stock for product: {$product->name}"
                );
            }
        }
    }

    /**
     * Process a single order line
     */
    private function processOrderLine(SalesOrder $order, array $itemData): void
    {
        $product = $this->productRepository->findById($itemData['product_id']);

        $order->lines()->create([
            'product_id' => $product->id,
            'quantity' => $itemData['quantity'],
            'unit_price' => $product->sales_price,
            'discount_percentage' => $itemData['discount_percentage'] ?? 0,
            'tax_rate' => $product->tax_rate ?? 0
        ]);
    }

    /**
     * Calculate order totals
     */
    private function calculateOrderTotals(SalesOrder $order): void
    {
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;

        foreach ($order->lines as $line) {
            $lineSubtotal = $line->quantity * $line->unit_price;
            $lineDiscount = $lineSubtotal * ($line->discount_percentage / 100);
            $lineTax = ($lineSubtotal - $lineDiscount) * ($line->tax_rate / 100);

            $subtotal += $lineSubtotal;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;
        }

        $total = $subtotal - $discountTotal + $taxTotal;

        $this->orderRepository->update($order, [
            'subtotal' => $subtotal,
            'discount_amount' => $discountTotal,
            'tax_amount' => $taxTotal,
            'total_amount' => $total
        ]);
    }
}
```

## Service Registration

Register services in the module's service provider:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Services\CustomerService;
use Modules\Sales\Services\OrderProcessingService;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register as singletons if they maintain state
        $this->app->singleton(CustomerService::class);
        $this->app->singleton(OrderProcessingService::class);

        // Or register as transient (new instance each time)
        $this->app->bind(NotificationService::class);
    }
}
```

## Using Services in Controllers

Controllers should be thin and delegate to services:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Sales\Http\Requests\CreateOrderRequest;
use Modules\Sales\Http\Resources\OrderResource;
use Modules\Sales\Services\OrderProcessingService;

class OrderController extends Controller
{
    public function __construct(
        private OrderProcessingService $orderService
    ) {}

    /**
     * Store a newly created order
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());
            return response()->json(new OrderResource($order), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Confirm an order
     */
    public function confirm(string $id): JsonResponse
    {
        try {
            $order = $this->orderService->confirmOrder($id);
            return response()->json(new OrderResource($order));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to confirm order',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## Domain Events

Services should trigger domain events for cross-cutting concerns:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Entities\SalesOrder;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SalesOrder $order
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Sales\Events\OrderCreated;
use Modules\Accounting\Services\AccountingService;

class CreateAccountingEntry implements ShouldQueue
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    /**
     * Handle the event
     */
    public function handle(OrderCreated $event): void
    {
        // Create accounting entry for the order
        $this->accountingService->recordSalesOrder($event->order);
    }
}
```

## Exception Handling

Create custom exceptions for domain-specific errors:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

class InsufficientStockException extends \Exception
{
    public function __construct(
        string $message = 'Insufficient stock available',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

class OrderNotFoundException extends \Exception
{
    public function __construct(string $orderId)
    {
        parent::__construct("Order not found: {$orderId}");
    }
}
```

## Testing Services

### 1. Unit Test with Mocked Dependencies

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use Modules\Sales\Services\OrderProcessingService;
use Modules\Sales\Repositories\Contracts\SalesOrderRepositoryInterface;
use Modules\Inventory\Services\InventoryService;

class OrderProcessingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_order_successfully(): void
    {
        // Arrange
        $mockOrderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $mockInventoryService = Mockery::mock(InventoryService::class);
        $mockNotificationService = Mockery::mock(NotificationService::class);

        $service = new OrderProcessingService(
            $mockOrderRepo,
            $mockInventoryService,
            $mockNotificationService
        );

        $orderData = [
            'customer_id' => 'uuid-123',
            'items' => [
                ['product_id' => 'prod-1', 'quantity' => 2]
            ]
        ];

        $mockInventoryService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn(true);

        $mockOrderRepo
            ->shouldReceive('create')
            ->once()
            ->andReturn(new SalesOrder($orderData));

        // Act
        $result = $service->createOrder($orderData);

        // Assert
        $this->assertInstanceOf(SalesOrder::class, $result);
    }

    public function test_it_throws_exception_when_stock_insufficient(): void
    {
        // Test exception handling
        $this->expectException(InsufficientStockException::class);

        // Test implementation
    }
}
```

### 2. Integration Test

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Product;
use Modules\Sales\Services\OrderProcessingService;

class OrderProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_order_with_valid_data(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $orderData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'discount_percentage' => 0
                ]
            ]
        ];

        $service = app(OrderProcessingService::class);

        // Act
        $order = $service->createOrder($orderData);

        // Assert
        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'draft'
        ]);

        $this->assertDatabaseHas('order_lines', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5
        ]);
    }
}
```

## Service Layer Best Practices

### 1. Single Responsibility
Each service should have a focused purpose:
- ✅ `CustomerService` - Customer management
- ✅ `OrderProcessingService` - Order lifecycle
- ✅ `InventoryService` - Stock management
- ❌ `SalesService` - Too broad, split into focused services

### 2. Dependency Injection
Always inject dependencies through constructor:
```php
public function __construct(
    private CustomerRepositoryInterface $customerRepository,
    private EmailService $emailService
) {}
```

### 3. Transaction Management
Use database transactions for multi-step operations:
```php
DB::beginTransaction();
try {
    // Multiple operations
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 4. Event-Driven Communication
Use events for cross-module communication:
```php
event(new OrderCreated($order));
```

### 5. Error Handling
Use custom exceptions for domain errors:
```php
throw new InsufficientStockException('Not enough stock');
```

### 6. Logging
Log important business operations:
```php
Log::info('Order created', ['order_id' => $order->id]);
Log::error('Failed to process order', ['error' => $e->getMessage()]);
```

## Common Pitfalls to Avoid

1. **Don't put business logic in controllers** - Move to services
2. **Don't inject repositories in controllers** - Use services
3. **Don't forget transactions** - Wrap multi-step operations
4. **Don't ignore errors** - Handle and log appropriately
5. **Don't create god services** - Keep services focused
6. **Don't forget to test** - Write unit and integration tests
7. **Don't mix concerns** - Separate validation, business logic, and data access
8. **Don't bypass the service layer** - Always use services from controllers

## Checklist

- [x] Define focused service classes
- [x] Inject dependencies via constructor
- [x] Use interfaces for repository dependencies
- [x] Wrap multi-step operations in transactions
- [x] Trigger domain events for cross-cutting concerns
- [x] Create custom exceptions for domain errors
- [x] Log important operations and errors
- [x] Write unit tests with mocked dependencies
- [x] Write integration tests with real dependencies
- [x] Keep services thin and focused
- [x] Document complex business logic
- [x] Use type hints and return types
