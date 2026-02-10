# Enum Migration Guide

## Overview

This guide provides instructions for migrating entity models from string constants to PHP 8.3 backed enums for type-safe status management.

## Why Enums?

### Problems with String Constants

```php
// OLD WAY - String constants
class Invoice extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    
    // Problem 1: No type safety
    $invoice->status = 'typo'; // Compiles, runtime error
    
    // Problem 2: No IDE autocomplete
    $invoice->status = Invoice::STATUS_; // Hard to find all statuses
    
    // Problem 3: Business logic scattered
    public function canBePaid(): bool
    {
        return in_array($this->status, ['sent', 'overdue']);
    }
}
```

### Benefits of Enums

```php
// NEW WAY - Enums
class Invoice extends Model
{
    protected $casts = [
        'status' => InvoiceStatusEnum::class,
    ];
    
    // Benefit 1: Type safety
    $invoice->status = InvoiceStatusEnum::DRAFT; // IDE autocomplete
    $invoice->status = 'typo'; // Type error at assignment
    
    // Benefit 2: Business logic in enum
    if ($invoice->status->canReceivePayment()) {
        // Payment logic
    }
    
    // Benefit 3: Workflow validation
    $nextStatuses = $invoice->status->nextStatuses();
}
```

## Available Enums

### Core Module

1. **StatusEnum** - Common status values
   - `DRAFT`, `PENDING`, `ACTIVE`, `INACTIVE`, `APPROVED`, `REJECTED`, `COMPLETED`, `CANCELLED`, `SUSPENDED`, `ARCHIVED`
   - Methods: `isFinal()`, `isEditable()`, `nextStatuses()`, `label()`, `color()`

2. **PriceTypeEnum** - Pricing calculation types
   - `FLAT`, `PERCENTAGE`, `TIERED`, `VOLUME`, `LOCATION_BASED`, `TIME_BASED`, `CUSTOMER_SPECIFIC`, `QUANTITY_BREAK`, `BUNDLE`, `DYNAMIC`
   - Methods: `requiresConfiguration()`, `supportsLocationPricing()`, `label()`, `description()`

3. **ProductTypeEnum** - Product classifications
   - `PRODUCT`, `SERVICE`, `COMBO`, `DIGITAL`, `SUBSCRIPTION`
   - Methods: `requiresInventory()`, `supportsVariableUnits()`, `allowsDifferentBuyingSelling()`, `supportsBundle()`

4. **OrganizationTypeEnum** - Hierarchical organization types
   - `CORPORATION`, `REGION`, `COUNTRY`, `STATE`, `BRANCH`, `DEPARTMENT`, `DIVISION`, `TEAM`, `FRANCHISE`, `SUBSIDIARY`
   - Methods: `hierarchyLevel()`, `canHaveChildren()`, `typicalParents()`

### Accounting Module

5. **AccountTypeEnum** - Chart of accounts
   - `ASSET`, `LIABILITY`, `EQUITY`, `REVENUE`, `EXPENSE`
   - Methods: `normalBalance()`, `isBalanceSheet()`, `isIncomeStatement()`, `financialStatement()`

6. **InvoiceStatusEnum** - Invoice lifecycle
   - `DRAFT`, `SENT`, `PARTIALLY_PAID`, `PAID`, `OVERDUE`, `CANCELLED`, `REFUNDED`, `WRITTEN_OFF`
   - Methods: `isEditable()`, `isFinal()`, `canReceivePayment()`, `color()`

7. **JournalEntryStatusEnum** - Journal entry workflow
   - `DRAFT`, `POSTED`, `REVERSED`, `VOID`
   - Methods: `isEditable()`, `affectsBalances()`, `isFinal()`

8. **FiscalPeriodTypeEnum** - Accounting periods
   - `YEAR`, `QUARTER`, `MONTH`, `WEEK`
   - Methods: `typicalDays()`, `periodsPerYear()`

### Sales Module

9. **OrderStatusEnum** - Sales order lifecycle
   - `DRAFT`, `PENDING`, `CONFIRMED`, `PROCESSING`, `SHIPPED`, `DELIVERED`, `COMPLETED`, `CANCELLED`, `RETURNED`, `REFUNDED`
   - Methods: `isEditable()`, `isFinal()`, `nextStatuses()`, `color()`

### Inventory Module

10. **StockMovementTypeEnum** - Stock movement classification
    - `RECEIPT`, `ISSUE`, `TRANSFER`, `ADJUSTMENT`, `RETURN`, `SCRAP`, `PRODUCTION`, `CONSUMPTION`, `CYCLE_COUNT`
    - Methods: `increasesStock()`, `decreasesStock()`, `requiresApproval()`, `transactionSign()`

11. **CostingMethodEnum** - Inventory valuation
    - `FIFO`, `LIFO`, `AVERAGE`, `STANDARD`, `SPECIFIC_IDENTIFICATION`
    - Methods: `requiresLotTracking()`, `allowsCostUpdates()`, `accountingTreatment()`

### HR Module

12. **EmployeeStatusEnum** - Employment lifecycle
    - `ACTIVE`, `PROBATION`, `ON_LEAVE`, `SUSPENDED`, `TERMINATED`, `RESIGNED`, `RETIRED`
    - Methods: `isEmployed()`, `canReceivePayroll()`, `isFinal()`, `color()`

13. **LeaveStatusEnum** - Leave request workflow
    - `PENDING`, `APPROVED`, `REJECTED`, `CANCELLED`, `TAKEN`
    - Methods: `canBeCancelled()`, `affectsBalance()`, `isFinal()`, `nextStatuses()`

### Procurement Module

14. **PurchaseOrderStatusEnum** - Purchase order lifecycle
    - `DRAFT`, `PENDING`, `APPROVED`, `SENT`, `CONFIRMED`, `PARTIALLY_RECEIVED`, `RECEIVED`, `CANCELLED`, `CLOSED`
    - Methods: `isEditable()`, `isFinal()`, `canReceiveGoods()`, `nextStatuses()`, `color()`

## Migration Steps

### Step 1: Update Entity Model

**Before:**
```php
<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'status',
        // ... other fields
    ];
}
```

**After:**
```php
<?php

declare(strict_types=1);

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Enums\InvoiceStatusEnum;

class Invoice extends Model
{
    protected $fillable = [
        'status',
        // ... other fields
    ];

    /**
     * Get the attributes that should be cast
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatusEnum::class,
        ];
    }
}
```

### Step 2: Update Business Logic

**Before:**
```php
public function canReceivePayment(): bool
{
    return in_array($this->status, [
        self::STATUS_SENT,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_OVERDUE,
    ], true);
}
```

**After:**
```php
public function canReceivePayment(): bool
{
    return $this->status->canReceivePayment();
}
```

### Step 3: Update Controllers

**Before:**
```php
public function create(Request $request)
{
    $invoice = Invoice::create([
        'status' => Invoice::STATUS_DRAFT,
        // ... other fields
    ]);
}
```

**After:**
```php
public function create(Request $request)
{
    $invoice = Invoice::create([
        'status' => InvoiceStatusEnum::DRAFT,
        // ... other fields
    ]);
}
```

### Step 4: Update Form Requests

**Before:**
```php
public function rules(): array
{
    return [
        'status' => ['required', 'in:draft,sent,paid,cancelled'],
    ];
}
```

**After:**
```php
use Modules\Accounting\Enums\InvoiceStatusEnum;

public function rules(): array
{
    return [
        'status' => ['required', 'in:' . implode(',', InvoiceStatusEnum::values())],
        // OR using Laravel's Enum rule (Laravel 9+)
        'status' => ['required', new Enum(InvoiceStatusEnum::class)],
    ];
}
```

### Step 5: Update Migrations

**Before:**
```php
$table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
```

**After:**
```php
use Modules\Accounting\Enums\InvoiceStatusEnum;

$table->string('status')->default(InvoiceStatusEnum::DRAFT->value);
// OR
$table->enum('status', InvoiceStatusEnum::values())->default(InvoiceStatusEnum::DRAFT->value);
```

### Step 6: Update Factories

**Before:**
```php
return [
    'status' => $this->faker->randomElement(['draft', 'sent', 'paid']),
];
```

**After:**
```php
use Modules\Accounting\Enums\InvoiceStatusEnum;

return [
    'status' => $this->faker->randomElement(InvoiceStatusEnum::cases()),
];
```

### Step 7: Update Seeders

**Before:**
```php
Invoice::create([
    'status' => 'draft',
    // ... other fields
]);
```

**After:**
```php
use Modules\Accounting\Enums\InvoiceStatusEnum;

Invoice::create([
    'status' => InvoiceStatusEnum::DRAFT,
    // ... other fields
]);
```

### Step 8: Update Tests

**Before:**
```php
$invoice = Invoice::factory()->create(['status' => 'draft']);
$this->assertEquals('draft', $invoice->status);
```

**After:**
```php
use Modules\Accounting\Enums\InvoiceStatusEnum;

$invoice = Invoice::factory()->create(['status' => InvoiceStatusEnum::DRAFT]);
$this->assertEquals(InvoiceStatusEnum::DRAFT, $invoice->status);
$this->assertTrue($invoice->status->isEditable());
```

## Common Patterns

### Workflow Validation

```php
// Check if status transition is allowed
$currentStatus = $invoice->status;
$newStatus = InvoiceStatusEnum::PAID;

if (!in_array($newStatus, $currentStatus->nextStatuses(), true)) {
    throw new InvalidStatusTransitionException(
        "Cannot change status from {$currentStatus->value} to {$newStatus->value}"
    );
}

$invoice->status = $newStatus;
$invoice->save();
```

### UI Display

```php
// In Blade template
<span class="badge badge-{{ $invoice->status->color() }}">
    {{ $invoice->status->label() }}
</span>

// In API Resource
return [
    'status' => [
        'value' => $invoice->status->value,
        'label' => $invoice->status->label(),
        'color' => $invoice->status->color(),
        'is_editable' => $invoice->status->isEditable(),
        'is_final' => $invoice->status->isFinal(),
    ],
];
```

### Query Scopes

```php
// In Model
public function scopeDraft($query)
{
    return $query->where('status', InvoiceStatusEnum::DRAFT);
}

public function scopeEditable($query)
{
    return $query->whereIn('status', [
        InvoiceStatusEnum::DRAFT,
        InvoiceStatusEnum::SENT,
    ]);
}

// Usage
$draftInvoices = Invoice::draft()->get();
$editableInvoices = Invoice::editable()->get();
```

### State Machine Pattern

```php
class InvoiceService
{
    public function changeStatus(Invoice $invoice, InvoiceStatusEnum $newStatus): void
    {
        // Validate transition
        if (!in_array($newStatus, $invoice->status->nextStatuses(), true)) {
            throw new InvalidStatusTransitionException(
                "Invalid status transition from {$invoice->status->value} to {$newStatus->value}"
            );
        }

        // Perform status-specific logic
        match ($newStatus) {
            InvoiceStatusEnum::SENT => $this->handleSent($invoice),
            InvoiceStatusEnum::PAID => $this->handlePaid($invoice),
            InvoiceStatusEnum::CANCELLED => $this->handleCancelled($invoice),
            default => null,
        };

        // Update status
        $invoice->status = $newStatus;
        $invoice->save();

        // Fire event
        event(new InvoiceStatusChanged($invoice, $newStatus));
    }
}
```

## Testing Enums

```php
<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use Modules\Accounting\Enums\InvoiceStatusEnum;

class InvoiceStatusEnumTest extends TestCase
{
    public function test_draft_is_editable(): void
    {
        $this->assertTrue(InvoiceStatusEnum::DRAFT->isEditable());
    }

    public function test_paid_is_final(): void
    {
        $this->assertTrue(InvoiceStatusEnum::PAID->isFinal());
    }

    public function test_sent_can_receive_payment(): void
    {
        $this->assertTrue(InvoiceStatusEnum::SENT->canReceivePayment());
    }

    public function test_workflow_transitions(): void
    {
        $draft = InvoiceStatusEnum::DRAFT;
        $sent = InvoiceStatusEnum::SENT;
        
        $this->assertContains($sent, $draft->nextStatuses());
        $this->assertNotContains(InvoiceStatusEnum::PAID, $draft->nextStatuses());
    }

    public function test_all_values(): void
    {
        $values = InvoiceStatusEnum::values();
        
        $this->assertIsArray($values);
        $this->assertContains('draft', $values);
        $this->assertContains('sent', $values);
        $this->assertContains('paid', $values);
    }
}
```

## Best Practices

### 1. Always Use Type Hints

```php
// Good
public function updateStatus(Invoice $invoice, InvoiceStatusEnum $status): void
{
    $invoice->status = $status;
}

// Bad
public function updateStatus(Invoice $invoice, $status): void
{
    $invoice->status = $status; // No type safety
}
```

### 2. Validate Transitions

```php
// Good - Validate before changing
if (in_array($newStatus, $currentStatus->nextStatuses(), true)) {
    $invoice->status = $newStatus;
}

// Bad - Change without validation
$invoice->status = $newStatus; // May violate business rules
```

### 3. Use Enum Methods

```php
// Good - Use enum business logic
if ($invoice->status->canReceivePayment()) {
    $this->processPayment($invoice);
}

// Bad - Duplicate logic
if (in_array($invoice->status, [InvoiceStatusEnum::SENT, InvoiceStatusEnum::OVERDUE])) {
    $this->processPayment($invoice);
}
```

### 4. Consistent Naming

```php
// Good - Consistent enum names across modules
OrderStatusEnum::DRAFT
InvoiceStatusEnum::DRAFT
PurchaseOrderStatusEnum::DRAFT

// Bad - Inconsistent naming
OrderStatusEnum::DRAFT
InvoiceStatusEnum::NEW
PurchaseOrderStatusEnum::CREATED
```

## Troubleshooting

### Issue: Enum not found

```php
// Error: Class 'InvoiceStatusEnum' not found

// Solution: Import the enum
use Modules\Accounting\Enums\InvoiceStatusEnum;
```

### Issue: Type error on assignment

```php
// Error: Cannot assign string to InvoiceStatusEnum

// Wrong:
$invoice->status = 'draft';

// Correct:
$invoice->status = InvoiceStatusEnum::DRAFT;
```

### Issue: Database value mismatch

```php
// Error: Database has 'draft' but enum expects 'DRAFT'

// Solution: Enum values are lowercase strings
InvoiceStatusEnum::DRAFT->value; // Returns 'draft' (lowercase)

// Database should store: 'draft', 'sent', 'paid' (lowercase)
```

## Migration Checklist

For each entity with status fields:

- [ ] Create/verify enum exists in `Modules/{Module}/Enums/`
- [ ] Remove string constants from entity model
- [ ] Add enum to `casts()` method in model
- [ ] Update business logic methods to use enum methods
- [ ] Update controllers to use enum cases
- [ ] Update Form Requests validation rules
- [ ] Update migrations to use enum values
- [ ] Update factories to use enum cases
- [ ] Update seeders to use enum cases
- [ ] Update tests to use enum cases
- [ ] Add enum unit tests
- [ ] Update API resources to format enum for JSON
- [ ] Update documentation

## Performance Considerations

- **Memory**: Enums are singletons, negligible memory impact
- **Speed**: Enum comparisons are faster than string comparisons
- **Database**: Store enum values as strings (lowercase) for compatibility
- **Caching**: Laravel automatically casts and caches enum instances

## See Also

- PHP 8.1+ Enums Documentation: https://www.php.net/manual/en/language.enumerations.php
- Laravel Enum Casting: https://laravel.com/docs/11.x/eloquent-mutators#enum-casting
- State Machine Pattern: https://en.wikipedia.org/wiki/Finite-state_machine
