# Module Dependency Graph

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


## Overview

This document visualizes the dependencies and relationships between all modules in the kv-saas-crm-erp system, showing how they interact through events, shared services, and data flows.

## Module Categories

### Core Modules (Foundation)
- **Core**: Base classes, interfaces, value objects
- **Tenancy**: Multi-tenant infrastructure
- **Authentication**: User authentication and authorization
- **Documents**: File storage and document management

### Business Modules
- **Sales**: Customer management, orders, quotes
- **Inventory**: Stock management, warehouses, products
- **Accounting**: Financial records, journal entries, reporting
- **HR**: Employee management, payroll, attendance
- **Procurement**: Purchase orders, suppliers, requisitions
- **Warehouse**: Picking, packing, shipping operations
- **CRM**: Customer relationship management, leads, opportunities

### Support Modules
- **Notifications**: Email, SMS, push notifications
- **Reporting**: Analytics, dashboards, exports
- **Analytics**: Business intelligence, metrics tracking

---

## Dependency Hierarchy

### Level 0: Core Infrastructure

```
┌─────────────────────────────────────────────────────────────┐
│                        Core Module                           │
│  • Base Entity, AggregateRoot, ValueObject                  │
│  • Repository interfaces                                    │
│  • Domain event infrastructure                              │
│  • Service interfaces                                       │
└─────────────────────────────────────────────────────────────┘
```

### Level 1: Platform Services

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│     Tenancy      │  │  Authentication  │  │    Documents     │
│  • Tenant model  │  │  • User model    │  │  • File storage  │
│  • DB switching  │  │  • Permissions   │  │  • S3 integration│
│  • Context mgmt  │  │  • Roles         │  │  • PDF generation│
└──────────────────┘  └──────────────────┘  └──────────────────┘
         ↑                     ↑                     ↑
         └─────────────────────┴─────────────────────┘
                            Depends on Core
```

### Level 2: Business Domain Modules

```
┌──────────────────────────────────────────────────────────────┐
│                    Business Modules                           │
│                                                              │
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐│
│  │ Sales  │  │Inventory│  │Account-│  │   HR   │  │Procure-││
│  │        │  │        │  │  ing   │  │        │  │  ment  ││
│  └────────┘  └────────┘  └────────┘  └────────┘  └────────┘│
│       ↑            ↑            ↑            ↑            ↑  │
│       └────────────┴────────────┴────────────┴────────────┘  │
│                    Depends on Platform Services              │
└──────────────────────────────────────────────────────────────┘
```

### Level 3: Support Services

```
┌──────────────────────────────────────────────────────────────┐
│                    Support Modules                            │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │Notifications │  │  Reporting   │  │  Analytics   │      │
│  │              │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│         ↑                  ↑                  ↑              │
│         └──────────────────┴──────────────────┘              │
│                 Depends on Business Modules                  │
└──────────────────────────────────────────────────────────────┘
```

---

## Detailed Module Dependencies

### Sales Module

**Depends On:**
- Core (base classes)
- Tenancy (tenant context)
- Authentication (user permissions)
- Documents (invoice PDFs)

**Depended By:**
- Accounting (creates invoices)
- Reporting (sales reports)
- Analytics (sales metrics)
- CRM (lead conversion)

**Events Published:**
- `CustomerCreated`
- `OrderPlaced`
- `OrderCancelled`
- `QuoteApproved`

**Events Consumed:**
- `StockReserved` (from Inventory)
- `InvoiceCreated` (from Accounting)
- `PaymentReceived` (from Accounting)

```
┌──────────────────────────────────────────────────────────┐
│                     Sales Module                          │
├──────────────────────────────────────────────────────────┤
│ Depends on:                                              │
│   Core → Tenancy → Authentication → Documents           │
├──────────────────────────────────────────────────────────┤
│ Publishes events:                                        │
│   → OrderPlaced                                          │
│   → CustomerCreated                                      │
├──────────────────────────────────────────────────────────┤
│ Consumes events:                                         │
│   ← StockReserved (Inventory)                           │
│   ← InvoiceCreated (Accounting)                         │
└──────────────────────────────────────────────────────────┘
```

### Inventory Module

**Depends On:**
- Core
- Tenancy
- Authentication
- Documents (product images, manuals)

**Depended By:**
- Sales (stock availability)
- Procurement (purchase orders)
- Warehouse (stock movements)
- Reporting (inventory reports)

**Events Published:**
- `StockReserved`
- `StockReleased`
- `StockAdjusted`
- `StockLowAlert`
- `ProductCreated`

**Events Consumed:**
- `OrderPlaced` (from Sales)
- `OrderCancelled` (from Sales)
- `GoodsReceived` (from Procurement)

```
┌──────────────────────────────────────────────────────────┐
│                   Inventory Module                        │
├──────────────────────────────────────────────────────────┤
│ Depends on:                                              │
│   Core → Tenancy → Authentication → Documents           │
├──────────────────────────────────────────────────────────┤
│ Publishes events:                                        │
│   → StockReserved                                        │
│   → StockLowAlert                                        │
├──────────────────────────────────────────────────────────┤
│ Consumes events:                                         │
│   ← OrderPlaced (Sales)                                 │
│   ← GoodsReceived (Procurement)                         │
└──────────────────────────────────────────────────────────┘
```

### Accounting Module

**Depends On:**
- Core
- Tenancy
- Authentication
- Documents (financial reports, invoices)

**Depended By:**
- Sales (revenue recognition)
- Procurement (AP)
- HR (payroll)
- Reporting (financial reports)

**Events Published:**
- `InvoiceCreated`
- `PaymentReceived`
- `JournalEntryCreated`
- `FiscalPeriodClosed`

**Events Consumed:**
- `OrderPlaced` (from Sales)
- `GoodsReceived` (from Procurement)
- `PayrollProcessed` (from HR)

```
┌──────────────────────────────────────────────────────────┐
│                  Accounting Module                        │
├──────────────────────────────────────────────────────────┤
│ Depends on:                                              │
│   Core → Tenancy → Authentication → Documents           │
├──────────────────────────────────────────────────────────┤
│ Publishes events:                                        │
│   → InvoiceCreated                                       │
│   → PaymentReceived                                      │
├──────────────────────────────────────────────────────────┤
│ Consumes events:                                         │
│   ← OrderPlaced (Sales)                                 │
│   ← GoodsReceived (Procurement)                         │
│   ← PayrollProcessed (HR)                               │
└──────────────────────────────────────────────────────────┘
```

### Procurement Module

**Depends On:**
- Core
- Tenancy
- Authentication
- Inventory (product catalog)
- Documents (PO documents)

**Depended By:**
- Accounting (AP)
- Inventory (stock replenishment)

**Events Published:**
- `RequisitionCreated`
- `PurchaseOrderCreated`
- `GoodsReceived`
- `SupplierInvoiceReceived`

**Events Consumed:**
- `StockLowAlert` (from Inventory)
- `RequisitionApproved` (internal)

```
┌──────────────────────────────────────────────────────────┐
│                  Procurement Module                       │
├──────────────────────────────────────────────────────────┤
│ Depends on:                                              │
│   Core → Tenancy → Authentication → Inventory → Docs    │
├──────────────────────────────────────────────────────────┤
│ Publishes events:                                        │
│   → PurchaseOrderCreated                                 │
│   → GoodsReceived                                        │
├──────────────────────────────────────────────────────────┤
│ Consumes events:                                         │
│   ← StockLowAlert (Inventory)                           │
└──────────────────────────────────────────────────────────┘
```

### HR Module

**Depends On:**
- Core
- Tenancy
- Authentication
- Documents (contracts, certifications)

**Depended By:**
- Accounting (payroll accounting)
- Reporting (HR reports)

**Events Published:**
- `EmployeeHired`
- `EmployeeTerminated`
- `PayrollProcessed`
- `LeaveRequested`

**Events Consumed:**
- (Mostly independent)

```
┌──────────────────────────────────────────────────────────┐
│                      HR Module                            │
├──────────────────────────────────────────────────────────┤
│ Depends on:                                              │
│   Core → Tenancy → Authentication → Documents           │
├──────────────────────────────────────────────────────────┤
│ Publishes events:                                        │
│   → EmployeeHired                                        │
│   → PayrollProcessed                                     │
├──────────────────────────────────────────────────────────┤
│ Consumes events:                                         │
│   (Minimal external dependencies)                        │
└──────────────────────────────────────────────────────────┘
```

---

## Event Flow Diagrams

### Sales Order Creation Flow

```
User creates order
        ↓
  ┌─────────────┐
  │   Sales     │ → OrderPlaced event
  │   Module    │
  └─────────────┘
         ↓
  ┌────────────────────────────────────────────┐
  │            Event Bus                       │
  └────────────────────────────────────────────┘
    ↓              ↓                ↓
┌─────────┐  ┌────────────┐  ┌──────────────┐
│Inventory│  │ Accounting │  │Notifications │
│ Module  │  │   Module   │  │    Module    │
└─────────┘  └────────────┘  └──────────────┘
    ↓              ↓                ↓
Reserve       Create         Send
Stock         Invoice        Email
    ↓              ↓                ↓
StockReserved InvoiceCreated NotificationSent
event         event          event
```

### Procurement to Accounting Flow

```
Stock Low Alert
        ↓
  ┌──────────────┐
  │  Inventory   │ → StockLowAlert event
  │   Module     │
  └──────────────┘
         ↓
  ┌──────────────┐
  │ Procurement  │ → RequisitionCreated event
  │   Module     │
  └──────────────┘
         ↓
  (Manual Approval)
         ↓
  ┌──────────────┐
  │ Procurement  │ → PurchaseOrderCreated event
  │   Module     │
  └──────────────┘
         ↓
  (Goods Received)
         ↓
  ┌──────────────┐
  │  Inventory   │ → GoodsReceived event
  │   Module     │
  └──────────────┘
         ↓
  ┌──────────────┐
  │ Accounting   │ → Creates AP Journal Entry
  │   Module     │
  └──────────────┘
```

---

## Shared Services

### Document Service (Used by All Modules)

```
┌───────────────────────────────────────────────────────┐
│                 Document Module                        │
├───────────────────────────────────────────────────────┤
│ Services:                                             │
│  • TenantStorageService                              │
│  • DocumentService                                   │
│  • ImageOptimizationService                          │
│  • VirusScanService                                  │
├───────────────────────────────────────────────────────┤
│ Used by:                                             │
│  • Sales → Invoice PDFs, Quote documents             │
│  • Inventory → Product images, Manuals               │
│  • Accounting → Financial reports                    │
│  • HR → Contracts, Certifications                    │
│  • Procurement → PO documents, Delivery notes        │
└───────────────────────────────────────────────────────┘
```

### Notification Service (Used by Most Modules)

```
┌───────────────────────────────────────────────────────┐
│             Notification Module                        │
├───────────────────────────────────────────────────────┤
│ Channels:                                             │
│  • Email (SMTP, SendGrid, Mailgun)                   │
│  • SMS (Twilio, Nexmo)                               │
│  • Push Notifications                                │
│  • In-App Notifications                              │
├───────────────────────────────────────────────────────┤
│ Triggered by:                                         │
│  • Sales → Order confirmations                       │
│  • Inventory → Stock alerts                          │
│  • Accounting → Payment reminders                    │
│  • HR → Leave approvals                              │
│  • Procurement → PO approvals                        │
└───────────────────────────────────────────────────────┘
```

---

## Module Activation Order

When booting the application, modules must be activated in dependency order:

```
1. Core (no dependencies)
   ↓
2. Tenancy (depends on Core)
   ↓
3. Authentication (depends on Core, Tenancy)
   ↓
4. Documents (depends on Core, Tenancy, Authentication)
   ↓
5. Business Modules (parallel, all depend on 1-4)
   • Sales
   • Inventory
   • Accounting
   • HR
   • Procurement
   • Warehouse
   • CRM
   ↓
6. Support Modules (parallel, depend on Business Modules)
   • Notifications
   • Reporting
   • Analytics
```

**Configuration:**

```json
// modules_statuses.json
{
    "Core": true,
    "Tenancy": true,
    "Authentication": true,
    "Documents": true,
    "Sales": true,
    "Inventory": true,
    "Accounting": true,
    "HR": true,
    "Procurement": true,
    "Warehouse": true,
    "CRM": true,
    "Notifications": true,
    "Reporting": true,
    "Analytics": true
}
```

```php
// app/Services/ModuleManager.php
public function getBootOrder(): array
{
    return [
        'Core',
        'Tenancy',
        'Authentication',
        'Documents',
        ['Sales', 'Inventory', 'Accounting', 'HR', 'Procurement'], // Parallel
        ['Notifications', 'Reporting', 'Analytics'], // Parallel
    ];
}
```

---

## Database Dependencies

### Shared Tables

```
Central Database (system-wide):
├── tenants
├── tenant_domains
├── users
├── permissions
├── roles
└── modules

Tenant Database (per-tenant):
├── customers
├── products
├── orders
├── invoices
├── employees
├── suppliers
└── ...all business tables
```

### Foreign Key Relationships

```
customers (Sales)
    ↓ (has many)
orders (Sales)
    ↓ (has many)
order_items (Sales)
    ← (belongs to) products (Inventory)

orders (Sales)
    ↓ (has one)
invoices (Accounting)
    ↓ (has many)
payments (Accounting)

purchase_orders (Procurement)
    ← (belongs to) suppliers (Procurement)
    ↓ (has many)
goods_receipts (Procurement)
    → (updates) stock_levels (Inventory)
```

---

## API Endpoint Dependencies

### Cross-Module API Calls

While modules should primarily communicate via events, read-only API calls are acceptable:

```php
// Sales module checking inventory
GET /api/v1/inventory/products/{id}/availability

// Accounting module fetching order details
GET /api/v1/sales/orders/{id}

// Reporting module aggregating data
GET /api/v1/sales/orders?period=monthly
GET /api/v1/inventory/stock-levels
GET /api/v1/accounting/revenue?period=monthly
```

**Best Practice:** Use internal service calls rather than HTTP requests:

```php
// Good: Direct service call
$availability = app(InventoryService::class)
    ->getProductAvailability($productId);

// Avoid: HTTP call between modules
$response = Http::get("/api/v1/inventory/products/{$productId}/availability");
```

---

## Testing Dependencies

### Unit Tests (No Dependencies)

```php
// Tests within same module
SalesModule\Tests\Unit\
├── CustomerTest.php        (no dependencies)
├── OrderTest.php           (no dependencies)
└── PricingServiceTest.php  (no dependencies)
```

### Integration Tests (With Dependencies)

```php
// Tests across modules
SalesModule\Tests\Integration\
├── OrderPlacementTest.php
│   └── Tests: Sales → Inventory → Accounting flow
└── CustomerCreationTest.php
    └── Tests: Sales → CRM → Notifications flow
```

### Test Execution Order

```bash
# 1. Core tests
php artisan test tests/Unit/Core

# 2. Individual module tests (parallel)
php artisan module:test Sales
php artisan module:test Inventory
php artisan module:test Accounting

# 3. Integration tests
php artisan test tests/Integration
```

---

## Dependency Management Best Practices

### 1. Declare Dependencies in Module Manifest

```json
{
    "name": "Sales",
    "dependencies": {
        "Core": "^1.0",
        "Tenancy": "^1.0",
        "Authentication": "^1.0",
        "Inventory": "^1.0",
        "Accounting": "^1.0"
    }
}
```

### 2. Use Events for Write Operations

```php
// ✓ Good: Event-driven
event(new OrderPlaced($order));

// ✗ Bad: Direct module coupling
app(InventoryService::class)->reserveStock($order);
```

### 3. Use Services for Read Operations

```php
// ✓ Good: Service dependency injection
public function __construct(
    private InventoryQueryService $inventoryQuery
) {}

// ✗ Bad: Direct model access
$product = \Modules\Inventory\Entities\Product::find($id);
```

### 4. Version Module APIs

```php
// v1 (stable)
Route::prefix('v1')->group(function () {
    Route::get('products/{id}', [ProductController::class, 'show']);
});

// v2 (new features)
Route::prefix('v2')->group(function () {
    Route::get('products/{id}', [ProductControllerV2::class, 'show']);
});
```

### 5. Document Cross-Module Contracts

```php
/**
 * @event OrderPlaced
 * @payload {
 *   order_id: int,
 *   customer_id: int,
 *   items: array<{product_id: int, quantity: int}>,
 *   total: float
 * }
 * @consumers [
 *   Inventory\Listeners\ReserveStock,
 *   Accounting\Listeners\CreateInvoice
 * ]
 */
```

---

## Visualization Legend

```
┌─────────┐
│ Module  │  = Module
└─────────┘

    ↓       = Depends on / Flows to
    
    →       = Publishes event
    
    ←       = Consumes event
    
    ↔       = Bidirectional dependency (avoid!)
```

---

## Conclusion

Understanding module dependencies is crucial for:

1. **Development**: Know what you need before building a module
2. **Testing**: Test in correct dependency order
3. **Deployment**: Activate modules in proper sequence
4. **Debugging**: Trace issues through event flows
5. **Scaling**: Identify bottlenecks and optimize communication
6. **Maintenance**: Minimize breaking changes across modules

For implementation details, refer to:
- [MODULE_DEVELOPMENT_GUIDE.md](MODULE_DEVELOPMENT_GUIDE.md)
- [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md)
- [LARAVEL_IMPLEMENTATION_TEMPLATES.md](LARAVEL_IMPLEMENTATION_TEMPLATES.md)
