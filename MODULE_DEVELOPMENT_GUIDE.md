# Module Development Guide

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


## Overview

This guide provides practical instructions for developing modules in the kv-saas-crm-erp system using Laravel and the nWidart/laravel-modules package with Odoo-inspired patterns.

## Table of Contents

1. [Setup and Prerequisites](#setup-and-prerequisites)
2. [Creating a New Module](#creating-a-new-module)
3. [Module Structure](#module-structure)
4. [Module Manifest](#module-manifest)
5. [Domain Models](#domain-models)
6. [Repositories](#repositories)
7. [Services](#services)
8. [Controllers](#controllers)
9. [Routes](#routes)
10. [Events and Listeners](#events-and-listeners)
11. [Multi-Tenant Considerations](#multi-tenant-considerations)
12. [Translations](#translations)
13. [Testing](#testing)
14. [Best Practices](#best-practices)

---

## Setup and Prerequisites

### 1. Install nWidart/laravel-modules

```bash
composer require nwidart/laravel-modules
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

### 3. Update composer.json

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "Modules/"
        }
    }
}
```

### 4. Dump Autoload

```bash
composer dump-autoload
```

---

## Creating a New Module

### Using Artisan Command

```bash
# Create a new module
php artisan module:make ModuleName

# Example: Create a Sales module
php artisan module:make Sales
```

This generates the following structure:

```
Modules/
└── Sales/
    ├── Config/
    ├── Console/
    ├── Database/
    ├── Entities/
    ├── Http/
    ├── Providers/
    ├── Resources/
    ├── Routes/
    ├── Tests/
    ├── composer.json
    └── module.json
```

---

## Module Structure

### Complete Module Layout

```
Modules/Sales/
├── Config/
│   └── config.php                    # Module configuration
├── Console/
│   └── Commands/                     # Artisan commands
├── Database/
│   ├── Migrations/                   # Database migrations
│   ├── Seeders/                      # Database seeders
│   └── Factories/                    # Model factories
├── Entities/                         # Domain models (Eloquent)
│   ├── Customer.php
│   ├── Lead.php
│   └── SalesOrder.php
├── Http/
│   ├── Controllers/                  # API/Web controllers
│   │   ├── API/
│   │   │   ├── CustomerController.php
│   │   │   └── OrderController.php
│   │   └── Web/
│   ├── Middleware/                   # Module-specific middleware
│   ├── Requests/                     # Form request validation
│   │   ├── CreateCustomerRequest.php
│   │   └── UpdateCustomerRequest.php
│   └── Resources/                    # API resources (transformers)
│       ├── CustomerResource.php
│       └── OrderResource.php
├── Providers/
│   ├── SalesServiceProvider.php      # Main service provider
│   └── RouteServiceProvider.php      # Route definitions
├── Repositories/                     # Data access layer
│   ├── Contracts/
│   │   └── CustomerRepositoryInterface.php
│   └── CustomerRepository.php
├── Services/                         # Business logic
│   ├── OrderProcessingService.php
│   └── PricingService.php
├── Events/                           # Domain events
│   ├── CustomerCreated.php
│   └── OrderPlaced.php
├── Listeners/                        # Event handlers
│   └── SendOrderConfirmation.php
├── Resources/                        # Views and assets
│   ├── assets/
│   │   ├── js/
│   │   └── css/
│   └── views/
│       ├── customers/
│       └── orders/
├── Routes/
│   ├── api.php                       # API routes
│   └── web.php                       # Web routes
├── Tests/
│   ├── Feature/                      # Feature tests
│   └── Unit/                         # Unit tests
├── composer.json                     # Module dependencies
└── module.json                       # Module metadata
```

---

## Module Manifest

### module.json Structure

```json
{
    "name": "Sales",
    "alias": "sales",
    "description": "Sales and CRM management module",
    "keywords": [
        "sales",
        "crm",
        "customers",
        "orders"
    ],
    "version": "1.0.0",
    "priority": 10,
    "providers": [
        "Modules\\Sales\\Providers\\SalesServiceProvider",
        "Modules\\Sales\\Providers\\RouteServiceProvider",
        "Modules\\Sales\\Providers\\EventServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": [
        "Inventory",
        "Accounting"
    ]
}
```

**Key Fields:**
- `name`: Module display name
- `alias`: Module identifier (lowercase, no spaces)
- `version`: Semantic versioning
- `priority`: Loading order (lower numbers load first)
- `providers`: Service providers to register
- `requires`: Dependencies on other modules

---

## Domain Models

### Creating a Model

```bash
php artisan module:make-model Customer Sales
```

### Example: Customer Model

```php
<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Traits\TenantScoped;
use Modules\Sales\Traits\Translatable;

class Customer extends Model
{
    use SoftDeletes, TenantScoped, Translatable;

    protected $fillable = [
        'customer_number',
        'name',
        'email',
        'phone',
        'type',
        'status',
        'credit_limit',
        'tenant_id',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'status' => 'string',
    ];

    protected $translatable = ['name', 'notes'];

    // Relationships
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function billingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('type', 'billing');
    }

    public function shippingAddresses()
    {
        return $this->morphMany(Address::class, 'addressable')
            ->where('type', 'shipping');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBusiness($query)
    {
        return $query->where('type', 'business');
    }

    // Accessors & Mutators
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    // Business Logic
    public function canPlaceOrder($amount)
    {
        $currentBalance = $this->getCurrentBalance();
        return ($currentBalance + $amount) <= $this->credit_limit;
    }

    public function getCurrentBalance()
    {
        return $this->salesOrders()
            ->whereIn('status', ['pending', 'approved'])
            ->sum('total_amount');
    }
}
```

### Tenant-Scoped Trait

```php
<?php

namespace Modules\Sales\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    protected static function bootTenantScoped()
    {
        // Auto-add tenant_id when creating
        static::creating(function (Model $model) {
            if (!$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id 
                    ?? app('tenant')->id;
            }
        });

        // Auto-scope all queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('tenant')) {
                $builder->where('tenant_id', app('tenant')->id);
            }
        });
    }
}
```

---

## Repositories

### Creating Repository Interface

```php
<?php

namespace Modules\Sales\Repositories\Contracts;

interface CustomerRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function paginate($perPage = 20);
    public function findByEmail($email);
    public function search($query);
}
```

### Implementing Repository

```php
<?php

namespace Modules\Sales\Repositories;

use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected $model;

    public function __construct(Customer $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $customer = $this->find($id);
        $customer->update($data);
        return $customer;
    }

    public function delete($id)
    {
        $customer = $this->find($id);
        return $customer->delete();
    }

    public function paginate($perPage = 20)
    {
        return $this->model->paginate($perPage);
    }

    public function findByEmail($email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function search($query)
    {
        return $this->model
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->paginate();
    }
}
```

### Binding Repository in Service Provider

```php
<?php

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Repositories\CustomerRepository;

class SalesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind repositories
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
    }
}
```

---

## Services

### Example: Order Processing Service

```php
<?php

namespace Modules\Sales\Services;

use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Inventory\Services\StockService;
use Illuminate\Support\Facades\DB;

class OrderProcessingService
{
    protected $customerRepo;
    protected $stockService;

    public function __construct(
        CustomerRepositoryInterface $customerRepo,
        StockService $stockService
    ) {
        $this->customerRepo = $customerRepo;
        $this->stockService = $stockService;
    }

    public function createOrder($customerId, array $items)
    {
        $customer = $this->customerRepo->find($customerId);

        return DB::transaction(function () use ($customer, $items) {
            // Calculate total
            $total = collect($items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            // Check credit limit
            if (!$customer->canPlaceOrder($total)) {
                throw new \Exception('Order exceeds customer credit limit');
            }

            // Create order
            $order = SalesOrder::create([
                'customer_id' => $customer->id,
                'order_date' => now(),
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            // Create order lines
            foreach ($items as $item) {
                $order->lines()->create($item);
                
                // Reserve stock
                $this->stockService->reserve(
                    $item['product_id'],
                    $item['quantity']
                );
            }

            // Fire event
            event(new OrderPlaced($order));

            return $order;
        });
    }

    public function approveOrder($orderId)
    {
        $order = SalesOrder::findOrFail($orderId);
        $order->update(['status' => 'approved']);

        // Additional business logic...

        return $order;
    }
}
```

---

## Controllers

### API Controller Example

```php
<?php

namespace Modules\Sales\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Http\Requests\CreateCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerController extends Controller
{
    protected $customerRepo;

    public function __construct(CustomerRepositoryInterface $customerRepo)
    {
        $this->customerRepo = $customerRepo;
    }

    /**
     * @OA\Get(
     *     path="/api/customers",
     *     summary="List all customers",
     *     tags={"Customers"},
     *     @OA\Response(response=200, description="Successful")
     * )
     */
    public function index(Request $request)
    {
        $customers = $request->has('search')
            ? $this->customerRepo->search($request->search)
            : $this->customerRepo->paginate($request->per_page ?? 20);

        return CustomerResource::collection($customers);
    }

    /**
     * @OA\Post(
     *     path="/api/customers",
     *     summary="Create a new customer",
     *     tags={"Customers"}
     * )
     */
    public function store(CreateCustomerRequest $request)
    {
        $customer = $this->customerRepo->create($request->validated());
        
        return new CustomerResource($customer);
    }

    /**
     * @OA\Get(
     *     path="/api/customers/{id}",
     *     summary="Get a specific customer",
     *     tags={"Customers"}
     * )
     */
    public function show($id)
    {
        $customer = $this->customerRepo->find($id);
        
        return new CustomerResource($customer);
    }

    /**
     * @OA\Put(
     *     path="/api/customers/{id}",
     *     summary="Update a customer",
     *     tags={"Customers"}
     * )
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        $customer = $this->customerRepo->update($id, $request->validated());
        
        return new CustomerResource($customer);
    }

    /**
     * @OA\Delete(
     *     path="/api/customers/{id}",
     *     summary="Delete a customer",
     *     tags={"Customers"}
     * )
     */
    public function destroy($id)
    {
        $this->customerRepo->delete($id);
        
        return response()->json(null, 204);
    }
}
```

### API Resource (Transformer)

```php
<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer_number' => $this->customer_number,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'type' => $this->type,
            'status' => $this->status,
            'credit_limit' => $this->credit_limit,
            'current_balance' => $this->getCurrentBalance(),
            'billing_address' => new AddressResource($this->whenLoaded('billingAddress')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

---

## Routes

### API Routes (Routes/api.php)

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\API\CustomerController;
use Modules\Sales\Http\Controllers\API\OrderController;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1')->group(function () {
    // Customers
    Route::apiResource('customers', CustomerController::class);
    
    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/approve', [OrderController::class, 'approve']);
});
```

### Web Routes (Routes/web.php)

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Web\CustomerController;

Route::middleware(['web', 'auth', 'tenant'])->prefix('sales')->group(function () {
    Route::resource('customers', CustomerController::class);
});
```

---

## Events and Listeners

### Creating Events

```bash
php artisan module:make-event OrderPlaced Sales
```

### Event Example

```php
<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Entities\SalesOrder;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public $order;

    public function __construct(SalesOrder $order)
    {
        $this->order = $order;
    }
}
```

### Listener Example

```php
<?php

namespace Modules\Sales\Listeners;

use Modules\Sales\Events\OrderPlaced;
use Modules\Notifications\Services\NotificationService;

class SendOrderConfirmation
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(OrderPlaced $event)
    {
        $order = $event->order;
        
        // Send email to customer
        $this->notificationService->sendEmail(
            $order->customer->email,
            'Order Confirmation',
            'emails.order-confirmation',
            ['order' => $order]
        );
    }
}
```

### Registering Events

```php
<?php

namespace Modules\Sales\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Listeners\SendOrderConfirmation;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendOrderConfirmation::class,
            'Modules\Inventory\Listeners\ReserveStock',
            'Modules\Accounting\Listeners\CreateInvoice',
        ],
    ];
}
```

---

## Multi-Tenant Considerations

### Tenant Middleware

```php
<?php

namespace Modules\Sales\Http\Middleware;

use Closure;

class TenantMiddleware
{
    public function handle($request, Closure $next)
    {
        // Identify tenant from subdomain or header
        $tenantId = $request->header('X-Tenant-ID') 
            ?? $this->getTenantFromSubdomain($request);

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant not identified'], 400);
        }

        // Load and bind tenant
        $tenant = Tenant::find($tenantId);
        app()->instance('tenant', $tenant);

        // Set tenant-specific database connection if using database-per-tenant
        if ($tenant->database_name) {
            config(['database.connections.tenant.database' => $tenant->database_name]);
            DB::purge('tenant');
        }

        return $next($request);
    }

    protected function getTenantFromSubdomain($request)
    {
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        
        return Tenant::where('subdomain', $subdomain)->value('id');
    }
}
```

---

## Translations

### Using Polymorphic Translatable Models

```php
// Save translations
$customer = Customer::find(1);
$customer->saveTranslation('en', [
    'name' => 'Acme Corporation',
    'notes' => 'Premium customer',
]);
$customer->saveTranslation('fr', [
    'name' => 'Société Acme',
    'notes' => 'Client premium',
]);

// Retrieve with current locale
app()->setLocale('fr');
$customer = Customer::find(1);
echo $customer->name; // "Société Acme"

// Get specific translation
$frenchCustomer = $customer->translate('fr');
```

---

## Testing

### Feature Test Example

```php
<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Sales\Entities\Customer;

class CustomerAPITest extends TestCase
{
    public function test_can_list_customers()
    {
        $this->actingAsTenant();
        
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_customer()
    {
        $this->actingAsTenant();

        $data = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'type' => 'business',
        ];

        $response = $this->postJson('/api/v1/customers', $data);

        $response->assertStatus(201)
            ->assertJson(['data' => ['name' => 'Test Customer']]);

        $this->assertDatabaseHas('customers', ['email' => 'test@example.com']);
    }
}
```

### Unit Test Example

```php
<?php

namespace Modules\Sales\Tests\Unit;

use Tests\TestCase;
use Modules\Sales\Entities\Customer;

class CustomerTest extends TestCase
{
    public function test_can_check_credit_limit()
    {
        $customer = Customer::factory()->create([
            'credit_limit' => 10000,
        ]);

        $this->assertTrue($customer->canPlaceOrder(5000));
        $this->assertFalse($customer->canPlaceOrder(15000));
    }
}
```

---

## Best Practices

### 1. Module Independence
- Minimize dependencies between modules
- Use events for cross-module communication
- Define clear interfaces

### 2. Naming Conventions
- Use PascalCase for class names
- Use snake_case for database columns
- Use camelCase for methods

### 3. Repository Pattern
- All database access through repositories
- Makes code testable
- Enables switching data sources

### 4. Service Layer
- Complex business logic in services
- Keep controllers thin
- Services coordinate between repositories

### 5. Event-Driven
- Use events for side effects
- Enables loose coupling
- Other modules can listen without modification

### 6. Testing
- Write tests for critical business logic
- Use factories for test data
- Mock external dependencies

### 7. Documentation
- Document public APIs with PHPDoc
- Use OpenAPI annotations for API endpoints
- Keep README updated

### 8. Versioning
- Use semantic versioning
- Document breaking changes
- Maintain changelog

---

## Module Development Checklist

- [ ] Create module structure
- [ ] Define module.json manifest
- [ ] Create domain models
- [ ] Implement repositories
- [ ] Build service layer
- [ ] Create controllers and routes
- [ ] Add validation (Form Requests)
- [ ] Implement API resources
- [ ] Define domain events
- [ ] Add event listeners
- [ ] Configure multi-tenant support
- [ ] Add translations if needed
- [ ] Write unit tests
- [ ] Write feature tests
- [ ] Add OpenAPI documentation
- [ ] Update module README
- [ ] Test module in isolation
- [ ] Test module integration

---

## Additional Resources

- [nWidart/laravel-modules Documentation](https://nwidart.com/laravel-modules/)
- [Laravel Documentation](https://laravel.com/docs)
- [Clean Architecture Principles](RESOURCE_ANALYSIS.md#1-clean-architecture--solid-principles)
- [Domain-Driven Design](ARCHITECTURE.md#3-domain-driven-design-ddd)
- [Multi-Tenant Patterns](RESOURCE_ANALYSIS.md#5-laravel-multi-tenant-architecture-emmy-awards)

---

## Getting Help

For questions or issues:
1. Check existing module examples
2. Review architectural documentation
3. Consult Laravel and nWidart documentation
4. Reach out to the development team
