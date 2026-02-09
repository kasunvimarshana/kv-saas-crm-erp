# Authorization Policies Quick Reference

Quick reference guide for using authorization policies in the Laravel 11 ERP/CRM system.

## Table of Contents
- [Policy Methods](#policy-methods)
- [Permission Format](#permission-format)
- [Common Roles](#common-roles)
- [Usage Patterns](#usage-patterns)
- [Custom Abilities](#custom-abilities)

## Policy Methods

All policies inherit these standard methods from `BasePolicy`:

```php
viewAny($user)                    // List resources
view($user, $model)               // View single resource
create($user)                     // Create new resource
update($user, $model)             // Update resource
delete($user, $model)             // Delete resource
restore($user, $model)            // Restore soft-deleted resource
forceDelete($user, $model)        // Permanently delete resource
```

## Permission Format

```
{resource}.{action}
```

Examples:
- `customer.view`
- `sales-order.confirm`
- `invoice.mark-paid`
- `employee.terminate`

## Common Roles

```php
'super-admin'           // Full system access, bypasses all checks
'admin'                // Organization administrator
'manager'              // Department/team manager
'finance-manager'      // Finance/accounting manager
'hr-manager'           // Human resources manager
'sales-manager'        // Sales department manager
'inventory-manager'    // Inventory/warehouse manager
'procurement-manager'  // Procurement department manager
'warehouse-manager'    // Warehouse operations manager
'payroll-manager'      // Payroll processing manager
'accountant'           // Accounting staff
'purchasing-agent'     // Procurement/purchasing staff
```

## Usage Patterns

### In Controllers

```php
// Authorize before action
$this->authorize('view', $customer);
$this->authorize('approveCreditLimit', $customer);

// Check in condition
if ($request->user()->can('update', $customer)) {
    // Allow update
}

// Deny with custom message
$this->authorize('delete', $customer) ?: abort(403, 'Cannot delete active customer');
```

### In Blade Templates

```php
// Show/hide elements
@can('view', $customer)
    <a href="{{ route('customers.show', $customer) }}">View</a>
@endcan

@can('update', $customer)
    <button>Edit</button>
@endcan

// Check custom ability
@can('approveCreditLimit', $customer)
    <button>Approve Credit Limit</button>
@endcan

// Multiple checks
@canany(['update', 'delete'], $customer)
    <div class="actions">...</div>
@endcanany
```

### In API Resources

```php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        // Include abilities
        'abilities' => [
            'can_view' => $request->user()->can('view', $this->resource),
            'can_update' => $request->user()->can('update', $this->resource),
            'can_delete' => $request->user()->can('delete', $this->resource),
            'can_approve' => $request->user()->can('approve', $this->resource),
        ],
    ];
}
```

### Using Gates

```php
use Illuminate\Support\Facades\Gate;

// Check gate
if (Gate::allows('manage-tenant')) {
    // User can manage tenant
}

// Deny access
Gate::authorize('manage-roles');

// Check multiple gates
if (Gate::any(['manage-users', 'manage-roles'])) {
    // User can manage users OR roles
}
```

## Custom Abilities by Module

### Sales Module

**Customer**
- `approveCreditLimit` - Approve credit limit changes
- `viewFinancialData` - View financial information
- `updateCreditLimit` - Update credit limit
- `mergeCustomers` - Merge duplicate customers
- `export` / `import` - Export/import customer data

**Lead**
- `convertToCustomer` - Convert qualified lead
- `assign` - Assign to another user
- `qualify` / `disqualify` - Mark lead status
- `viewActivities` - View lead activities

**Sales Order**
- `confirmOrder` / `cancelOrder` - Confirm/cancel order
- `approve` - Approve pending order
- `createInvoice` - Generate invoice
- `scheduleDelivery` - Schedule delivery
- `updatePricing` - Update order pricing
- `applyDiscount` - Apply discounts

### Inventory Module

**Product**
- `activate` / `deactivate` - Activate/deactivate product
- `updatePricing` - Update product pricing
- `updateStock` - Adjust stock levels
- `viewCost` - View cost information
- `duplicate` - Duplicate product
- `manageVariants` - Manage variants

**Warehouse**
- `manageStock` - Manage warehouse stock
- `performStockCount` / `approveStockCount` - Stock counting
- `transferStock` - Transfer between warehouses
- `viewReports` - View warehouse reports
- `configureSettings` - Configure warehouse

**Stock Movement**
- `approveMovement` / `rejectMovement` - Approve/reject
- `complete` - Complete approved movement
- `cancel` - Cancel movement
- `reverse` - Reverse completed movement

### Accounting Module

**Account**
- `markSystemAccount` - Mark as system account
- `activate` / `deactivate` - Activate/deactivate
- `reconcile` - Reconcile account
- `viewBalance` / `viewTransactions` - View details

**Invoice**
- `send` - Send to customer
- `markPaid` / `markVoid` - Mark status
- `approve` / `cancel` - Approve/cancel
- `applyPayment` - Apply payment
- `sendReminder` - Send payment reminder
- `createCreditNote` - Create credit note

**Journal Entry**
- `post` / `reverse` - Post/reverse entry
- `approve` / `reject` - Approve/reject
- `submit` - Submit for approval
- `closePeriod` - Close fiscal period

**Payment**
- `process` / `reconcile` - Process/reconcile
- `approve` / `reject` - Approve/reject
- `cancel` / `refund` / `void` - Cancel/refund/void
- `allocate` - Allocate to invoices

### HR Module

**Employee**
- `terminate` / `reactivate` - Terminate/reactivate
- `viewSalary` / `updateSalary` - Salary management
- `viewPerformanceReviews` - View reviews
- `promote` - Promote employee
- `transfer` - Transfer department
- `viewDocuments` / `manageDocuments` - Document management

**Leave**
- `approve` / `reject` / `cancel` - Approve/reject/cancel
- `viewBalance` / `overrideBalance` - Balance management
- `submit` - Submit request

**Payroll**
- `process` - Process draft payroll
- `approve` / `reject` / `finalize` - Approve/reject/finalize
- `reverse` - Reverse finalized
- `recalculate` - Recalculate payroll
- `generateReport` / `export` - Reports/export

**Attendance**
- `checkIn` / `checkOut` - Check in/out
- `approve` / `reject` - Approve/reject
- `correct` / `override` - Correct/override
- `viewReports` / `export` - Reports/export

### Procurement Module

**Supplier**
- `rateSupplier` - Rate performance
- `activate` / `deactivate` - Activate/deactivate
- `approve` - Approve new supplier
- `blacklist` - Blacklist supplier
- `viewPricing` / `viewPerformance` - View details

**Purchase Requisition**
- `approve` / `reject` - Approve/reject
- `convertToPo` - Convert to purchase order
- `submit` / `resubmit` - Submit/resubmit
- `cancel` - Cancel requisition
- `assign` - Assign to user

**Purchase Order**
- `send` / `confirm` / `receive` - Send/confirm/receive
- `approve` / `cancel` - Approve/cancel
- `createInvoice` - Create invoice
- `returnGoods` - Return to supplier
- `updatePricing` - Update pricing
- `close` - Close order

## Common Patterns

### Status-Based Authorization

```php
// Only allow delete on draft status
public function delete($user, $model): bool
{
    return parent::delete($user, $model) && $model->status === 'draft';
}

// Only allow confirm on pending/draft
public function confirm($user, $model): bool
{
    return $this->checkPermission($user, 'confirm') &&
           in_array($model->status, ['draft', 'pending']);
}
```

### Owner-Based Authorization

```php
// Allow update by owner or managers
public function update($user, $model): bool
{
    return parent::update($user, $model) &&
           ($this->isOwner($user, $model) || $this->hasAnyRole($user, ['admin', 'manager']));
}

// Prevent self-approval
public function approve($user, $model): bool
{
    return $this->checkPermission($user, 'approve') &&
           !$this->isOwner($user, $model);
}
```

### Role-Based Authorization

```php
// Require specific roles
public function sensitive($user, $model): bool
{
    return $this->checkPermission($user, 'sensitive') &&
           $this->hasAnyRole($user, ['admin', 'super-admin']);
}

// Require all roles
public function complex($user, $model): bool
{
    return $this->checkPermission($user, 'complex') &&
           $this->hasAllRoles($user, ['role1', 'role2']);
}
```

### Tenant Isolation

```php
// All methods include tenant checks automatically
public function view($user, $model): bool
{
    return $this->checkPermission($user, 'view') &&
           $this->checkTenantIsolation($user, $model); // ✓ Tenant check
}
```

## Testing Policies

```php
use Tests\TestCase;

class CustomerPolicyTest extends TestCase
{
    public function test_user_can_approve_credit_limit()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');
        $customer = Customer::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $this->assertTrue($user->can('approveCreditLimit', $customer));
    }
    
    public function test_user_cannot_access_different_tenant()
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $customer = Customer::factory()->create(['tenant_id' => 2]);
        
        $this->assertFalse($user->can('view', $customer));
    }
}
```

## Troubleshooting

**Problem**: Policy not working
```php
// Solution 1: Check if policy is registered
// In AuthServiceProvider
protected $policies = [
    YourModel::class => YourModelPolicy::class,
];

// Solution 2: Clear cache
php artisan optimize:clear
```

**Problem**: Permission denied
```php
// Check if user has permission
dd($user->hasPermissionTo('resource.action'));

// Check if user has role
dd($user->hasRole('manager'));

// Check tenant
dd($model->tenant_id === $user->tenant_id);
```

## Additional Resources

- Full documentation: `AUTHORIZATION_POLICIES.md`
- Laravel Docs: https://laravel.com/docs/11.x/authorization
- Spatie Permissions: https://spatie.be/docs/laravel-permission/v6
