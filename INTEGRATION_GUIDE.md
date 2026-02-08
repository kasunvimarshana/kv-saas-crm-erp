# Integration Guide: Connecting All Architectural Patterns

## Overview

This guide demonstrates how all the architectural patterns, concepts, and components documented in this repository integrate to form a cohesive, enterprise-grade SaaS ERP/CRM system. It bridges theory and practice by showing how Clean Architecture, DDD, multi-tenancy, modular design, and Laravel-specific patterns work together.

## Table of Contents

1. [System Integration Overview](#1-system-integration-overview)
2. [Request Lifecycle with Multi-Tenancy](#2-request-lifecycle-with-multi-tenancy)
3. [Module Communication Patterns](#3-module-communication-patterns)
4. [Data Flow Examples](#4-data-flow-examples)
5. [Cross-Cutting Concerns](#5-cross-cutting-concerns)
6. [Practical Integration Scenarios](#6-practical-integration-scenarios)
7. [Deployment Architecture](#7-deployment-architecture)
8. [Development Workflow](#8-development-workflow)

---

## 1. System Integration Overview

### High-Level Architecture Map

```
┌───────────────────────────────────────────────────────────────────┐
│                         Client Layer                              │
│  (Web UI, Mobile Apps, Third-Party Integrations)                  │
└───────────────────────────────────────────────────────────────────┘
                              ↓
┌───────────────────────────────────────────────────────────────────┐
│                      API Gateway / Load Balancer                   │
│          (Nginx, Tenant Resolution, Rate Limiting)                 │
└───────────────────────────────────────────────────────────────────┘
                              ↓
┌───────────────────────────────────────────────────────────────────┐
│                   Laravel Application Layer                        │
│  ┌─────────────────────────────────────────────────────────────┐  │
│  │              Middleware Pipeline                            │  │
│  │  • CORS • Authentication • Tenant Resolution • RBAC         │  │
│  └─────────────────────────────────────────────────────────────┘  │
│                              ↓                                     │
│  ┌─────────────────────────────────────────────────────────────┐  │
│  │                   Module Layer                              │  │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │  │
│  │  │  Sales   │ │Inventory │ │Accounting│ │    HR    │       │  │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │  │
│  │       ↓             ↓             ↓             ↓            │  │
│  │  ┌─────────────────────────────────────────────────────┐    │  │
│  │  │            Event Bus (Domain Events)                │    │  │
│  │  └─────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────┘  │
│                              ↓                                     │
│  ┌─────────────────────────────────────────────────────────────┐  │
│  │              Core Services Layer                            │  │
│  │  • Repository Pattern • Service Layer • Domain Models       │  │
│  └─────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────────┘
                              ↓
┌───────────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐          │
│  │PostgreSQL│  │  Redis   │  │ RabbitMQ │  │   S3     │          │
│  │(Tenant DB)│  │ (Cache) │  │ (Queue)  │  │(Storage) │          │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘          │
└───────────────────────────────────────────────────────────────────┘
```

### Layer Responsibilities

**Client Layer:**
- User interface (web, mobile)
- API consumers
- Third-party integrations

**API Gateway:**
- Load balancing
- SSL/TLS termination
- Initial tenant resolution
- Rate limiting

**Middleware Pipeline:**
- Cross-cutting concerns
- Tenant context initialization
- Authentication and authorization
- Request validation

**Module Layer:**
- Business logic encapsulation
- Domain-specific operations
- Inter-module communication via events

**Core Services:**
- Shared business logic
- Repository implementations
- Domain model definitions

**Infrastructure:**
- Data persistence
- Caching
- Message queuing
- File storage

---

## 2. Request Lifecycle with Multi-Tenancy

### Complete Request Flow

Let's trace a request to create a customer through the entire system:

#### Step 1: Client Request

```http
POST /api/v1/customers
Host: acme-corp.saas-erp.com
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "type": "business"
}
```

#### Step 2: Nginx/Load Balancer

```nginx
# nginx.conf
server {
    listen 443 ssl http2;
    server_name *.saas-erp.com;
    
    # Extract subdomain for tenant identification
    if ($host ~* ^([^.]+)\.saas-erp\.com$) {
        set $tenant $1;
    }
    
    location / {
        proxy_pass http://laravel-app:9000;
        proxy_set_header X-Tenant-ID $tenant;
        proxy_set_header Host $host;
    }
}
```

#### Step 3: Laravel Middleware Pipeline

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api',                    // Rate limiting
        \App\Http\Middleware\Cors::class,  // CORS headers
        'auth:sanctum',                    // Authentication
        \App\Http\Middleware\InitializeTenancy::class,  // Tenant resolution
        \App\Http\Middleware\EnsureTenantActive::class, // Tenant validation
        \App\Http\Middleware\CheckPermissions::class,   // Authorization
    ],
];
```

**Middleware Execution Order:**

```
1. ThrottleRequests (Rate Limiting)
   ↓ Checks: Request rate within limits?
   ↓ Action: Continue or 429 Too Many Requests
   
2. Cors Middleware
   ↓ Checks: Valid origin?
   ↓ Action: Add CORS headers
   
3. Sanctum Authentication
   ↓ Checks: Valid Bearer token?
   ↓ Action: Load authenticated user or 401 Unauthorized
   
4. InitializeTenancy
   ↓ Checks: Can identify tenant from subdomain/header?
   ↓ Action: Set tenant context or 404 Tenant Not Found
   ↓ Code:
   
   $tenant = Tenant::where('id', $tenantId)->first();
   tenancy()->initialize($tenant);
   
   // Switch database connection
   DB::purge('tenant');
   config([
       'database.connections.tenant.database' => "tenant_{$tenant->id}"
   ]);
   DB::reconnect('tenant');
   
5. EnsureTenantActive
   ↓ Checks: Tenant status == 'active'?
   ↓ Action: Continue or 403 Tenant Inactive
   
6. CheckPermissions
   ↓ Checks: User has 'sales.create' permission?
   ↓ Action: Continue or 403 Forbidden
```

#### Step 4: Route Resolution

```php
// Modules/Sales/Routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('v1')
    ->group(function () {
        Route::apiResource('customers', CustomerController::class);
    });
```

#### Step 5: Controller Action

```php
// Modules/Sales/Http/Controllers/Api/CustomerController.php
public function store(CreateCustomerRequest $request): JsonResponse
{
    // 1. Request already validated by CreateCustomerRequest
    // 2. Tenant context already set by middleware
    // 3. User already authenticated
    
    // Delegate to service layer
    $customer = $this->customerService->create($request->validated());
    
    // Return resource response
    return (new CustomerResource($customer))
        ->response()
        ->setStatusCode(201);
}
```

#### Step 6: Service Layer

```php
// Modules/Sales/Services/CustomerService.php
public function create(array $data): Customer
{
    // 1. Apply business rules
    if ($this->isDuplicateEmail($data['email'])) {
        throw new DuplicateEmailException();
    }
    
    // 2. Create customer via repository
    $customer = $this->customerRepository->create([
        'tenant_id' => tenant('id'),  // Auto-injected
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'type' => $data['type'],
        'status' => 'active',
        'created_by' => auth()->id(),
    ]);
    
    // 3. Dispatch domain event
    event(new CustomerCreated($customer));
    
    // 4. Return customer
    return $customer;
}
```

#### Step 7: Repository Layer

```php
// Modules/Sales/Repositories/CustomerRepository.php
public function create(array $data): Customer
{
    // Create in tenant-specific database
    // Global scope automatically adds tenant_id filter
    return Customer::create($data);
}
```

#### Step 8: Domain Event Processing

```php
// Event is dispatched asynchronously via queue

// Modules/Sales/Events/CustomerCreated.php
class CustomerCreated extends DomainEvent
{
    public static function fromCustomer(Customer $customer): static
    {
        return static::create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
        ]);
    }
}

// Multiple listeners react to this event:

// 1. Modules/CRM/Listeners/CreateCRMRecord.php
public function handle(CustomerCreated $event): void
{
    // Create CRM record for the customer
    $this->crmService->createFromCustomer($event->getCustomerId());
}

// 2. Modules/Notifications/Listeners/SendWelcomeEmail.php
public function handle(CustomerCreated $event): void
{
    // Send welcome email to customer
    Mail::to($event->getCustomerEmail())
        ->send(new WelcomeEmail($event->getCustomerName()));
}

// 3. Modules/Analytics/Listeners/TrackCustomerCreation.php
public function handle(CustomerCreated $event): void
{
    // Track analytics event
    $this->analyticsService->track('customer.created', [
        'customer_id' => $event->getCustomerId(),
        'tenant_id' => $event->getTenantId(),
    ]);
}
```

#### Step 9: Response

```json
HTTP/1.1 201 Created
Content-Type: application/json

{
  "data": {
    "id": 123,
    "tenant_id": "acme-corp",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "type": "business",
    "status": "active",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

---

## 3. Module Communication Patterns

### Event-Driven Communication

Modules communicate through domain events to maintain loose coupling:

```
┌──────────────────────────────────────────────────────────────┐
│                      Event Bus                                │
└──────────────────────────────────────────────────────────────┘
   ↑                    ↑                    ↑
   │ Publish            │ Publish            │ Publish
   │                    │                    │
┌──┴────────┐      ┌───┴───────┐      ┌────┴──────┐
│   Sales   │      │ Inventory │      │Accounting │
│  Module   │      │  Module   │      │  Module   │
└───────────┘      └───────────┘      └───────────┘
   │                    │                    │
   │ Subscribe          │ Subscribe          │ Subscribe
   ↓                    ↓                    ↓
┌──────────────────────────────────────────────────────────────┐
│                      Event Bus                                │
└──────────────────────────────────────────────────────────────┘
```

#### Example: Order Placement Flow

```php
// 1. Sales Module: Order is placed
class OrderService
{
    public function placeOrder(array $data): SalesOrder
    {
        $order = $this->orderRepository->create($data);
        
        // Publish event
        event(OrderPlaced::fromOrder($order));
        
        return $order;
    }
}

// 2. Inventory Module: Reserve stock
class ReserveStock implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->getItems() as $item) {
            $this->stockService->reserve(
                $item['product_id'],
                $item['quantity'],
                "order:{$event->getOrderId()}"
            );
        }
        
        // If stock reserved, publish confirmation
        event(StockReserved::forOrder($event->getOrderId()));
    }
}

// 3. Accounting Module: Create invoice
class CreateInvoice implements ShouldQueue
{
    public function handle(StockReserved $event): void
    {
        $order = SalesOrder::find($event->getOrderId());
        
        $invoice = $this->invoiceService->createFromOrder($order);
        
        // Publish event
        event(InvoiceCreated::fromInvoice($invoice));
    }
}

// 4. Notifications Module: Notify customer
class SendOrderConfirmation implements ShouldQueue
{
    public function handle(InvoiceCreated $event): void
    {
        $invoice = Invoice::find($event->getInvoiceId());
        
        Mail::to($invoice->customer->email)
            ->send(new OrderConfirmationEmail($invoice));
    }
}
```

### Event Registration

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    // Order lifecycle events
    OrderPlaced::class => [
        ReserveStock::class,
        TrackOrderMetrics::class,
    ],
    
    StockReserved::class => [
        CreateInvoice::class,
        UpdateOrderStatus::class,
    ],
    
    InvoiceCreated::class => [
        SendOrderConfirmation::class,
        RecordAccountsReceivable::class,
        GenerateInvoicePDF::class,
    ],
    
    // Stock management events
    StockLowAlert::class => [
        CreatePurchaseRequisition::class,
        NotifyInventoryManager::class,
    ],
    
    // Payment events
    PaymentReceived::class => [
        RecordPayment::class,
        UpdateInvoiceStatus::class,
        SendPaymentReceipt::class,
    ],
];
```

---

## 4. Data Flow Examples

### Example 1: Sales Order Creation with Full Integration

```
User creates sales order
        ↓
┌───────────────────────────────────────────────────────────┐
│ Step 1: Sales Module - Validate and Create Order          │
│ • Validate customer exists                                │
│ • Check product availability                              │
│ • Calculate totals with tax                               │
│ • Create order record                                     │
│ • Publish: OrderPlaced event                              │
└───────────────────────────────────────────────────────────┘
        ↓
┌───────────────────────────────────────────────────────────┐
│ Step 2: Inventory Module - Reserve Stock                  │
│ • Listen: OrderPlaced event                               │
│ • Lock stock items                                        │
│ • Create stock reservations                               │
│ • Update available quantities                             │
│ • Publish: StockReserved event                            │
└───────────────────────────────────────────────────────────┘
        ↓
┌───────────────────────────────────────────────────────────┐
│ Step 3: Accounting Module - Create Invoice                │
│ • Listen: StockReserved event                             │
│ • Generate invoice number                                 │
│ • Create invoice with line items                          │
│ • Create A/R journal entry                                │
│ • Publish: InvoiceCreated event                           │
└───────────────────────────────────────────────────────────┘
        ↓
┌───────────────────────────────────────────────────────────┐
│ Step 4: Documents Module - Generate PDF                   │
│ • Listen: InvoiceCreated event                            │
│ • Generate invoice PDF                                    │
│ • Store in tenant-specific S3 bucket                      │
│ • Create document record                                  │
│ • Publish: DocumentGenerated event                        │
└───────────────────────────────────────────────────────────┘
        ↓
┌───────────────────────────────────────────────────────────┐
│ Step 5: Notifications Module - Send Confirmations         │
│ • Listen: InvoiceCreated, DocumentGenerated               │
│ • Send email to customer with PDF attachment              │
│ • Send SMS notification                                   │
│ • Create in-app notification                              │
│ • Publish: NotificationSent event                         │
└───────────────────────────────────────────────────────────┘
        ↓
┌───────────────────────────────────────────────────────────┐
│ Step 6: Analytics Module - Track Metrics                  │
│ • Listen: OrderPlaced, InvoiceCreated                     │
│ • Update sales metrics                                    │
│ • Track customer lifetime value                           │
│ • Update dashboard KPIs                                   │
└───────────────────────────────────────────────────────────┘
```

### Example 2: Multi-Currency Transaction Processing

```php
// Sales Order in EUR (Customer's currency)
$order = SalesOrder::create([
    'customer_id' => $customer->id,
    'currency' => 'EUR',
    'subtotal' => 1000.00,
    'tax' => 190.00,
    'total' => 1190.00,
]);

// System stores in multiple representations:

// 1. Original currency (EUR)
OrderAmount::create([
    'order_id' => $order->id,
    'currency' => 'EUR',
    'amount' => 1190.00,
    'type' => 'original',
]);

// 2. Tenant base currency (USD)
$exchangeRate = ExchangeRate::getRate('EUR', 'USD', now());
$amountUSD = 1190.00 * $exchangeRate->rate; // 1309.00

OrderAmount::create([
    'order_id' => $order->id,
    'currency' => 'USD',
    'amount' => $amountUSD,
    'exchange_rate' => $exchangeRate->rate,
    'type' => 'base',
]);

// 3. Accounting entries in base currency
JournalEntry::create([
    'date' => now(),
    'reference' => "SO-{$order->number}",
    'currency' => 'USD',
    'lines' => [
        [
            'account' => '1200', // Accounts Receivable
            'debit' => $amountUSD,
            'credit' => 0,
        ],
        [
            'account' => '4000', // Sales Revenue
            'debit' => 0,
            'credit' => $amountUSD - ($order->tax * $exchangeRate->rate),
        ],
        [
            'account' => '2200', // Sales Tax Payable
            'debit' => 0,
            'credit' => $order->tax * $exchangeRate->rate,
        ],
    ],
]);

// Reporting can now:
// - Show customer statements in EUR
// - Generate financial reports in USD
// - Provide multi-currency dashboards
```

### Example 3: Polymorphic Document Attachment

```php
// Any entity can have documents attached

// 1. Sales Order → Invoice PDF
$invoice = Invoice::find(123);
$pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);

Document::create([
    'tenant_id' => tenant('id'),
    'entity_type' => Invoice::class,
    'entity_id' => $invoice->id,
    'name' => "Invoice-{$invoice->number}.pdf",
    'file_path' => $storageService->store($pdf->output(), 'invoices'),
    'mime_type' => 'application/pdf',
    'disk' => 'documents',
    'category' => 'invoice',
]);

// 2. Employee → Contract Document
$employee = Employee::find(456);

Document::create([
    'tenant_id' => tenant('id'),
    'entity_type' => Employee::class,
    'entity_id' => $employee->id,
    'name' => "Employment-Contract-{$employee->employee_number}.pdf",
    'file_path' => $uploadedFile->store('hr/contracts', 'documents'),
    'mime_type' => $uploadedFile->getMimeType(),
    'disk' => 'documents',
    'category' => 'contract',
]);

// 3. Product → Image Gallery
$product = Product::find(789);

foreach ($request->file('images') as $image) {
    $optimized = $imageService->uploadAndOptimize($image);
    
    Document::create([
        'tenant_id' => tenant('id'),
        'entity_type' => Product::class,
        'entity_id' => $product->id,
        'name' => $image->getClientOriginalName(),
        'file_path' => $optimized['large'],
        'mime_type' => $image->getMimeType(),
        'disk' => 'images',
        'category' => 'product_image',
        'metadata' => [
            'thumbnail' => $optimized['thumbnail'],
            'medium' => $optimized['medium'],
            'large' => $optimized['large'],
        ],
    ]);
}

// Retrieve documents polymorphically
$invoice->documents; // All invoice documents
$employee->documents()->where('category', 'contract')->get(); // Only contracts
$product->images; // Product images (via relationship scope)
```

---

## 5. Cross-Cutting Concerns

### Tenant Isolation

**Global Scope for All Tenant Models:**

```php
// app/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (tenancy()->initialized) {
            $builder->where('tenant_id', tenant('id'));
        }
    }
}

// Apply to all tenant models
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (tenancy()->initialized) {
                $model->tenant_id = tenant('id');
            }
        });
    }
}

// Usage in models
class Customer extends Model
{
    use BelongsToTenant;
}
```

### Audit Trail

```php
// app/Observers/AuditObserver.php
class AuditObserver
{
    public function created(Model $model): void
    {
        AuditLog::create([
            'tenant_id' => tenant('id'),
            'user_id' => auth()->id(),
            'action' => 'created',
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => null,
            'new_values' => $model->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function updated(Model $model): void
    {
        AuditLog::create([
            'tenant_id' => tenant('id'),
            'user_id' => auth()->id(),
            'action' => 'updated',
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $model->getOriginal(),
            'new_values' => $model->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Translation System

```php
// Get translated product information
$product = Product::with('translations')->find(1);

// Current locale translation
echo $product->name; // Automatically uses app()->getLocale()

// Specific locale
echo $product->translate('es')->name; // Spanish name

// All translations
foreach ($product->translations as $translation) {
    echo "{$translation->locale}: {$translation->name}\n";
}

// Set translation
$product->setTranslation('fr', [
    'name' => 'Produit Exemple',
    'description' => 'Description en français',
]);
```

### Permission System

```php
// Define permissions in module manifest
"permissions": [
    "sales.view",
    "sales.create",
    "sales.edit",
    "sales.delete",
    "sales.export"
]

// Check in controller
class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sales.view')->only(['index', 'show']);
        $this->middleware('permission:sales.create')->only('store');
        $this->middleware('permission:sales.edit')->only('update');
        $this->middleware('permission:sales.delete')->only('destroy');
    }
}

// Check in blade/views
@can('sales.create')
    <button>Create Customer</button>
@endcan

// Check programmatically
if (auth()->user()->can('sales.export')) {
    return $this->exportService->exportCustomers();
}
```

---

## 6. Practical Integration Scenarios

### Scenario 1: Complete Purchase-to-Pay Cycle

```php
/**
 * Procurement Module triggers purchase requisition
 */
class PurchaseRequisitionService
{
    public function createRequisition(array $data): PurchaseRequisition
    {
        $requisition = PurchaseRequisition::create($data);
        
        event(RequisitionCreated::fromRequisition($requisition));
        
        return $requisition;
    }
}

/**
 * Procurement Module creates purchase order after approval
 */
class CreatePurchaseOrder implements ShouldQueue
{
    public function handle(RequisitionApproved $event): void
    {
        $requisition = PurchaseRequisition::find($event->getRequisitionId());
        
        $po = PurchaseOrder::create([
            'supplier_id' => $requisition->supplier_id,
            'requisition_id' => $requisition->id,
            'items' => $requisition->items,
            'total' => $requisition->total,
        ]);
        
        event(PurchaseOrderCreated::fromPO($po));
    }
}

/**
 * Inventory Module receives goods
 */
class ReceiveGoods implements ShouldQueue
{
    public function handle(GoodsReceived $event): void
    {
        foreach ($event->getItems() as $item) {
            $this->stockService->receive(
                $item['product_id'],
                $item['quantity'],
                $item['warehouse_id'],
                "PO-{$event->getPONumber()}"
            );
        }
        
        event(GoodsReceivedInInventory::fromGR($event));
    }
}

/**
 * Accounting Module creates AP and matches invoice
 */
class RecordSupplierInvoice implements ShouldQueue
{
    public function handle(SupplierInvoiceReceived $event): void
    {
        $invoice = $event->getInvoice();
        $po = PurchaseOrder::find($invoice->po_id);
        $gr = GoodsReceipt::where('po_id', $po->id)->first();
        
        // Three-way matching
        if ($this->threeWayMatch($po, $gr, $invoice)) {
            // Create AP journal entry
            JournalEntry::create([
                'lines' => [
                    ['account' => '5000', 'debit' => $invoice->total],  // Expense
                    ['account' => '2000', 'credit' => $invoice->total], // AP
                ],
            ]);
            
            event(InvoiceMatched::fromInvoice($invoice));
        }
    }
}

/**
 * Payment processing
 */
class ProcessPayment implements ShouldQueue
{
    public function handle(PaymentDue $event): void
    {
        $payment = Payment::create([
            'invoice_id' => $event->getInvoiceId(),
            'amount' => $event->getAmount(),
            'method' => 'bank_transfer',
        ]);
        
        // Create payment journal entry
        JournalEntry::create([
            'lines' => [
                ['account' => '2000', 'debit' => $payment->amount],  // AP
                ['account' => '1000', 'credit' => $payment->amount], // Cash
            ],
        ]);
        
        event(PaymentProcessed::fromPayment($payment));
    }
}
```

### Scenario 2: Multi-Module Reporting

```php
/**
 * Generate comprehensive sales report across modules
 */
class SalesReportService
{
    public function generateReport(string $period): array
    {
        // Sales data from Sales module
        $orders = $this->salesRepository->getOrdersForPeriod($period);
        
        // Inventory impact from Inventory module
        $stockMovements = $this->inventoryRepository
            ->getMovementsForOrders($orders->pluck('id'));
        
        // Financial data from Accounting module
        $revenues = $this->accountingRepository
            ->getRevenueForPeriod($period);
        
        // Customer analytics from CRM module
        $customerMetrics = $this->crmRepository
            ->getCustomerMetrics($period);
        
        return [
            'summary' => [
                'total_orders' => $orders->count(),
                'total_revenue' => $revenues->sum('amount'),
                'avg_order_value' => $orders->avg('total'),
                'new_customers' => $customerMetrics['new_count'],
            ],
            'orders' => OrderResource::collection($orders),
            'stock_impact' => $stockMovements,
            'revenue_breakdown' => $revenues->groupBy('account'),
            'customer_segments' => $customerMetrics['segments'],
        ];
    }
}
```

### Scenario 3: File Management Integration

```php
/**
 * Generate and attach invoice PDF
 */
class InvoiceService
{
    public function createInvoice(SalesOrder $order): Invoice
    {
        // 1. Create invoice record
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'total' => $order->total,
        ]);
        
        // 2. Generate PDF
        $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
        
        // 3. Store in tenant-specific S3 bucket
        $path = $this->storageService->store(
            $pdf->output(),
            "invoices/{$invoice->number}.pdf",
            'documents'
        );
        
        // 4. Create document record
        $document = Document::create([
            'tenant_id' => tenant('id'),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'name' => "Invoice-{$invoice->number}.pdf",
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'disk' => 'documents',
            'category' => 'invoice',
        ]);
        
        // 5. Dispatch event
        event(InvoiceCreated::fromInvoice($invoice, $document));
        
        return $invoice;
    }
}

/**
 * Email invoice to customer
 */
class SendInvoiceEmail implements ShouldQueue
{
    public function handle(InvoiceCreated $event): void
    {
        $invoice = Invoice::with('documents')->find($event->getInvoiceId());
        $document = $invoice->documents->first();
        
        // Generate temporary signed URL for PDF
        $pdfUrl = Storage::disk($document->disk)
            ->temporaryUrl($document->file_path, now()->addHours(24));
        
        Mail::to($invoice->customer->email)
            ->send(new InvoiceEmail($invoice, $pdfUrl));
    }
}
```

---

## 7. Deployment Architecture

### Production Environment Setup

```yaml
# docker-compose.production.yml
version: '3.8'

services:
  # Application servers (scaled horizontally)
  app:
    image: kv-saas-erp:${VERSION}
    deploy:
      replicas: 5
      update_config:
        parallelism: 1
        delay: 10s
      restart_policy:
        condition: on-failure
    environment:
      - APP_ENV=production
      - DB_CONNECTION=pgsql
      - CACHE_DRIVER=redis
      - QUEUE_CONNECTION=rabbitmq
      - FILESYSTEM_DISK=s3
    networks:
      - app-network
    depends_on:
      - postgres
      - redis
      - rabbitmq

  # Load balancer
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./nginx/ssl:/etc/nginx/ssl
    networks:
      - app-network
    depends_on:
      - app

  # Queue workers (scaled independently)
  worker:
    image: kv-saas-erp:${VERSION}
    command: php artisan queue:work --tries=3
    deploy:
      replicas: 3
    environment:
      - APP_ENV=production
      - QUEUE_CONNECTION=rabbitmq
    networks:
      - app-network
    depends_on:
      - rabbitmq

  # Scheduler
  scheduler:
    image: kv-saas-erp:${VERSION}
    command: php artisan schedule:work
    deploy:
      replicas: 1
    networks:
      - app-network

  # Database (primary)
  postgres:
    image: postgres:16-alpine
    environment:
      - POSTGRES_DB=erp_central
      - POSTGRES_USER=${DB_USERNAME}
      - POSTGRES_PASSWORD=${DB_PASSWORD}
    volumes:
      - postgres-data:/var/lib/postgresql/data
    networks:
      - app-network

  # Cache
  redis:
    image: redis:7-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - app-network

  # Message queue
  rabbitmq:
    image: rabbitmq:3-management-alpine
    environment:
      - RABBITMQ_DEFAULT_USER=${RABBITMQ_USER}
      - RABBITMQ_DEFAULT_PASS=${RABBITMQ_PASSWORD}
    volumes:
      - rabbitmq-data:/var/lib/rabbitmq
    networks:
      - app-network

networks:
  app-network:
    driver: overlay

volumes:
  postgres-data:
  redis-data:
  rabbitmq-data:
```

### High Availability Setup

```
┌─────────────────────────────────────────────────────────────┐
│                     Cloud Load Balancer                      │
│                     (AWS ELB / Azure LB)                     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              Availability Zone 1 (Primary)                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │  App Node 1 │  │  App Node 2 │  │  App Node 3 │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│  ┌─────────────┐  ┌─────────────┐                           │
│  │  Worker 1   │  │  Worker 2   │                           │
│  └─────────────┘  └─────────────┘                           │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              Availability Zone 2 (Failover)                  │
│  ┌─────────────┐  ┌─────────────┐                           │
│  │  App Node 4 │  │  App Node 5 │                           │
│  └─────────────┘  └─────────────┘                           │
│  ┌─────────────┐                                             │
│  │  Worker 3   │                                             │
│  └─────────────┘                                             │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    Data Layer (HA)                           │
│  ┌──────────────────────┐  ┌──────────────────────┐         │
│  │ PostgreSQL Primary   │→→│ PostgreSQL Replica   │         │
│  │  (Read/Write)        │  │  (Read Only)         │         │
│  └──────────────────────┘  └──────────────────────┘         │
│  ┌──────────────────────┐  ┌──────────────────────┐         │
│  │ Redis Primary        │→→│ Redis Replica        │         │
│  └──────────────────────┘  └──────────────────────┘         │
│  ┌──────────────────────┐                                    │
│  │ RabbitMQ Cluster     │                                    │
│  │  (3-node cluster)    │                                    │
│  └──────────────────────┘                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. Development Workflow

### Local Development Setup

```bash
# 1. Clone repository
git clone https://github.com/kasunvimarshana/kv-saas-crm-erp.git
cd kv-saas-crm-erp

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Start local services
docker-compose up -d

# 5. Run migrations
php artisan migrate
php artisan tenants:migrate

# 6. Seed data
php artisan db:seed
php artisan module:seed

# 7. Start development server
php artisan serve

# 8. Start queue worker (separate terminal)
php artisan queue:work

# 9. Start frontend build (separate terminal)
npm run dev
```

### Creating a New Feature

```bash
# 1. Create feature branch
git checkout -b feature/customer-loyalty-program

# 2. Create new module
php artisan module:make Loyalty

# 3. Generate module components
php artisan module:make-model LoyaltyProgram Loyalty
php artisan module:make-controller LoyaltyProgramController Loyalty --api
php artisan module:make-migration create_loyalty_programs_table Loyalty
php artisan module:make-request CreateLoyaltyProgramRequest Loyalty
php artisan module:make-resource LoyaltyProgramResource Loyalty

# 4. Define module manifest
# Edit Modules/Loyalty/module.json

# 5. Implement business logic
# Edit models, services, controllers

# 6. Write tests
php artisan module:make-test LoyaltyProgramTest Loyalty

# 7. Run tests
php artisan module:test Loyalty

# 8. Generate API documentation
php artisan l5-swagger:generate

# 9. Commit and push
git add .
git commit -m "feat: Add customer loyalty program module"
git push origin feature/customer-loyalty-program

# 10. Create pull request
```

### Module Integration Checklist

- [ ] Module manifest (module.json) created
- [ ] Dependencies declared in manifest
- [ ] Permissions defined in manifest
- [ ] Repository pattern implemented
- [ ] Service layer created
- [ ] Domain events defined
- [ ] Event listeners registered
- [ ] API endpoints documented (OpenAPI)
- [ ] Tenant isolation implemented
- [ ] Multi-language support added
- [ ] Unit tests written (>80% coverage)
- [ ] Integration tests written
- [ ] Migration files created
- [ ] Seeders created
- [ ] API resources created
- [ ] Form requests with validation
- [ ] Policies for authorization
- [ ] Documentation updated

---

## Conclusion

This integration guide demonstrates how all architectural patterns work together in the kv-saas-crm-erp system:

1. **Clean Architecture** ensures business logic independence
2. **Multi-Tenancy** provides complete data isolation
3. **Event-Driven Design** enables loose coupling between modules
4. **Repository Pattern** abstracts data access
5. **Domain Events** coordinate cross-module workflows
6. **Polymorphic Patterns** provide flexible relationships
7. **Laravel Features** accelerate development with best practices

### Key Integration Points

- **Middleware Pipeline**: Handles cross-cutting concerns (auth, tenancy, permissions)
- **Event Bus**: Coordinates module communication
- **Repository Layer**: Provides consistent data access
- **Service Layer**: Encapsulates business logic
- **Domain Events**: Enable reactive programming
- **File Storage**: Supports tenant-isolated document management
- **Multi-Currency**: Handles international transactions
- **Translation System**: Supports multi-language operations

### Best Practices for Integration

1. **Always use events** for cross-module communication
2. **Never access another module's database** directly
3. **Use repositories** for all data access
4. **Implement tenant scopes** on all models
5. **Test integration points** thoroughly
6. **Document event flows** for complex processes
7. **Monitor queue performance** for async operations
8. **Use database transactions** for critical operations
9. **Implement idempotency** for event handlers
10. **Log all integration points** for debugging

This comprehensive integration approach ensures scalability, maintainability, and flexibility for enterprise-grade SaaS ERP/CRM systems.
