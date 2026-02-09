# Authorization Policies Documentation

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


This document provides comprehensive documentation for the authorization policies implemented in the Laravel 11 ERP/CRM system.

## Overview

The system implements a robust Role-Based Access Control (RBAC) and Attribute-Based Access Control (ABAC) authorization framework using Laravel's built-in policy system and Spatie's Laravel Permission package.

## Architecture

### Base Policy

**Location**: `app/Policies/BasePolicy.php`

The `BasePolicy` is an abstract class that provides:

- **Standard CRUD methods**: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`
- **Tenant isolation checks**: Ensures users can only access data within their tenant
- **Permission checking**: Uses Spatie's permission system
- **Role checking**: Helper methods for role-based authorization
- **Owner checks**: Methods to verify resource ownership
- **Department checks**: Methods for department-based authorization

All module-specific policies extend this base class.

### Key Features

1. **Multi-Tenancy Support**: Every policy method includes tenant isolation checks
2. **Permission-Based**: Uses Spatie's `hasPermissionTo()` method
3. **Role-Based**: Includes role checking with `hasRole()` and `hasAnyRole()`
4. **Super Admin Bypass**: Super admins bypass all permission checks
5. **Extensible**: Easy to override methods in child policies

## Policy Naming Convention

Policies follow this naming pattern:
```
{Entity}Policy.php
```

Examples:
- `CustomerPolicy` for `Customer` entity
- `SalesOrderPolicy` for `SalesOrder` entity
- `InvoicePolicy` for `Invoice` entity

## Permission Naming Convention

Permissions follow this pattern:
```
{resource}.{action}
```

Examples:
- `customer.view` - View customers
- `customer.create` - Create customers
- `customer.approve-credit-limit` - Approve customer credit limits
- `sales-order.confirm` - Confirm sales orders

## Module Policies

### Sales Module

#### CustomerPolicy
**Location**: `Modules/Sales/Policies/CustomerPolicy.php`

**Standard Permissions**:
- `customer.view` - View customers
- `customer.create` - Create customers
- `customer.update` - Update customers
- `customer.delete` - Delete customers

**Custom Abilities**:
- `approveCreditLimit()` - Approve credit limits (requires admin/manager/finance-manager role)
- `viewFinancialData()` - View customer financial data
- `updateCreditLimit()` - Update credit limits (requires admin/finance-manager role)
- `mergeCustomers()` - Merge duplicate customers
- `export()` - Export customer data
- `import()` - Import customer data

**Usage Example**:
```php
// In controller
$this->authorize('approveCreditLimit', $customer);

// In blade
@can('approveCreditLimit', $customer)
    <button>Approve Credit Limit</button>
@endcan
```

#### LeadPolicy
**Location**: `Modules/Sales/Policies/LeadPolicy.php`

**Custom Abilities**:
- `convertToCustomer()` - Convert qualified leads to customers
- `assign()` - Assign lead to another user
- `qualify()` - Mark lead as qualified
- `disqualify()` - Mark lead as disqualified
- `viewActivities()` - View lead activities

**Special Rules**:
- Only lead owners or managers can update leads
- Only qualified leads can be converted to customers

#### SalesOrderPolicy
**Location**: `Modules/Sales/Policies/SalesOrderPolicy.php`

**Custom Abilities**:
- `confirmOrder()` - Confirm sales order
- `cancelOrder()` - Cancel sales order
- `approve()` - Approve pending order
- `createInvoice()` - Create invoice from order
- `scheduleDelivery()` - Schedule delivery
- `updatePricing()` - Update order pricing
- `applyDiscount()` - Apply discounts

**Special Rules**:
- Only draft orders can be deleted
- Only confirmed orders can generate invoices
- Pricing updates only allowed on draft/pending orders

### Inventory Module

#### ProductPolicy
**Location**: `Modules/Inventory/Policies/ProductPolicy.php`

**Custom Abilities**:
- `activate()` - Activate inactive product
- `deactivate()` - Deactivate active product
- `updatePricing()` - Update product pricing
- `updateStock()` - Adjust stock levels
- `viewCost()` - View product cost information
- `duplicate()` - Duplicate product
- `manageVariants()` - Manage product variants

**Special Rules**:
- Cost information only visible to admin/manager/finance-manager
- Stock updates restricted to inventory/warehouse managers

#### WarehousePolicy
**Location**: `Modules/Inventory/Policies/WarehousePolicy.php`

**Custom Abilities**:
- `manageStock()` - Manage warehouse stock
- `performStockCount()` - Perform physical stock count
- `approveStockCount()` - Approve stock count adjustments
- `transferStock()` - Transfer stock between warehouses
- `viewReports()` - View warehouse reports
- `configureSettings()` - Configure warehouse settings
- `activate()` / `deactivate()` - Activate/deactivate warehouse

#### StockMovementPolicy
**Location**: `Modules/Inventory/Policies/StockMovementPolicy.php`

**Custom Abilities**:
- `approveMovement()` - Approve pending movement
- `rejectMovement()` - Reject pending movement
- `complete()` - Complete approved movement
- `cancel()` - Cancel movement
- `reverse()` - Reverse completed movement

**Special Rules**:
- Only draft/pending/cancelled movements can be deleted
- Only approved movements can be completed
- Only completed movements can be reversed

### Accounting Module

#### AccountPolicy
**Location**: `Modules/Accounting/Policies/AccountPolicy.php`

**Custom Abilities**:
- `markSystemAccount()` - Mark as system account (super-admin only)
- `activate()` / `deactivate()` - Activate/deactivate account
- `reconcile()` - Reconcile account
- `viewBalance()` - View account balance
- `viewTransactions()` - View account transactions

**Special Rules**:
- System accounts cannot be deleted
- Only super-admin can modify system accounts

#### InvoicePolicy
**Location**: `Modules/Accounting/Policies/InvoicePolicy.php`

**Custom Abilities**:
- `send()` - Send invoice to customer
- `markPaid()` - Mark invoice as paid
- `markVoid()` - Mark invoice as void
- `approve()` - Approve draft invoice
- `cancel()` - Cancel invoice
- `applyPayment()` - Apply payment to invoice
- `sendReminder()` - Send payment reminder
- `createCreditNote()` - Create credit note

**Special Rules**:
- Only draft invoices can be deleted
- Only draft/approved invoices can be updated
- Paid/void invoices cannot be modified

#### JournalEntryPolicy
**Location**: `Modules/Accounting/Policies/JournalEntryPolicy.php`

**Custom Abilities**:
- `post()` - Post journal entry
- `reverse()` - Reverse posted entry
- `approve()` - Approve pending entry
- `reject()` - Reject pending entry
- `submit()` - Submit for approval
- `closePeriod()` - Close fiscal period (admin/super-admin only)

**Special Rules**:
- Users cannot approve their own entries
- Only draft/rejected entries can be modified
- Only posted entries can be reversed

#### PaymentPolicy
**Location**: `Modules/Accounting/Policies/PaymentPolicy.php`

**Custom Abilities**:
- `process()` - Process pending payment
- `reconcile()` - Reconcile completed payment
- `approve()` - Approve pending payment
- `reject()` - Reject pending payment
- `cancel()` - Cancel payment
- `refund()` - Refund completed payment
- `void()` - Void payment
- `allocate()` - Allocate payment to invoices

**Special Rules**:
- Users cannot approve their own payments
- Only completed payments can be reconciled/refunded
- Only draft/pending/failed payments can be deleted

### HR Module

#### EmployeePolicy
**Location**: `Modules/HR/Policies/EmployeePolicy.php`

**Custom Abilities**:
- `terminate()` - Terminate employee
- `reactivate()` - Reactivate terminated employee
- `viewSalary()` - View salary information
- `updateSalary()` - Update salary
- `viewPerformanceReviews()` - View performance reviews
- `promote()` - Promote employee
- `transfer()` - Transfer to another department
- `viewDocuments()` / `manageDocuments()` - Manage employee documents

**Special Rules**:
- Employees can view their own profile
- Salary info only visible to self or HR/finance managers
- Only active employees can be terminated

#### LeavePolicy
**Location**: `Modules/HR/Policies/LeavePolicy.php`

**Custom Abilities**:
- `approve()` - Approve leave request
- `reject()` - Reject leave request
- `cancel()` - Cancel leave request
- `viewBalance()` - View leave balance
- `overrideBalance()` - Override leave balance (HR only)
- `submit()` - Submit leave request

**Special Rules**:
- Users cannot approve their own leave requests
- Only owners can update draft leave requests
- Department managers can approve leaves in their department

#### PayrollPolicy
**Location**: `Modules/HR/Policies/PayrollPolicy.php`

**Custom Abilities**:
- `process()` - Process draft payroll
- `approve()` - Approve processed payroll
- `reject()` - Reject processed payroll
- `finalize()` - Finalize approved payroll
- `reverse()` - Reverse finalized payroll (admin/super-admin only)
- `recalculate()` - Recalculate payroll
- `generateReport()` - Generate payroll reports
- `export()` - Export payroll data

**Special Rules**:
- Users cannot approve their own payroll
- Only draft/rejected payroll can be modified
- Finalized payroll can only be reversed by super-admin

#### AttendancePolicy
**Location**: `Modules/HR/Policies/AttendancePolicy.php`

**Custom Abilities**:
- `checkIn()` - Check in attendance
- `checkOut()` - Check out attendance
- `approve()` - Approve attendance
- `reject()` - Reject attendance
- `correct()` - Correct attendance (HR only)
- `override()` - Override attendance (HR only)
- `viewReports()` - View attendance reports
- `export()` - Export attendance data

**Special Rules**:
- Users can view/update their own attendance
- Department managers can approve attendance in their department

### Procurement Module

#### SupplierPolicy
**Location**: `Modules/Procurement/Policies/SupplierPolicy.php`

**Custom Abilities**:
- `rateSupplier()` - Rate supplier performance
- `activate()` / `deactivate()` - Activate/deactivate supplier
- `approve()` - Approve new supplier
- `blacklist()` - Blacklist supplier (admin/manager only)
- `viewPricing()` - View supplier pricing
- `viewPerformance()` - View supplier performance
- `export()` - Export supplier data

#### PurchaseRequisitionPolicy
**Location**: `Modules/Procurement/Policies/PurchaseRequisitionPolicy.php`

**Custom Abilities**:
- `approve()` - Approve requisition
- `reject()` - Reject requisition
- `convertToPo()` - Convert to purchase order
- `submit()` - Submit for approval
- `cancel()` - Cancel requisition
- `resubmit()` - Resubmit rejected requisition
- `assign()` - Assign to another user

**Special Rules**:
- Users cannot approve their own requisitions
- Only approved requisitions can be converted to PO
- Only draft/rejected requisitions can be modified

#### PurchaseOrderPolicy
**Location**: `Modules/Procurement/Policies/PurchaseOrderPolicy.php`

**Custom Abilities**:
- `send()` - Send PO to supplier
- `confirm()` - Confirm purchase order
- `receive()` - Receive goods
- `approve()` - Approve pending PO
- `cancel()` - Cancel purchase order
- `createInvoice()` - Create invoice from PO
- `returnGoods()` - Return goods to supplier
- `updatePricing()` - Update PO pricing
- `close()` - Close purchase order

**Special Rules**:
- Only draft POs can be deleted
- Only confirmed POs can receive goods
- Pricing updates only on draft/pending POs

## Usage Guide

### In Controllers

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function show(Customer $customer)
    {
        // Authorize view action
        $this->authorize('view', $customer);
        
        return view('customers.show', compact('customer'));
    }

    public function approveCreditLimit(Customer $customer)
    {
        // Authorize custom ability
        $this->authorize('approveCreditLimit', $customer);
        
        // Approve credit limit logic
        $customer->update(['credit_limit_approved' => true]);
        
        return redirect()->back();
    }
}
```

### In Blade Templates

```blade
@can('view', $customer)
    <a href="{{ route('customers.show', $customer) }}">View</a>
@endcan

@can('update', $customer)
    <a href="{{ route('customers.edit', $customer) }}">Edit</a>
@endcan

@can('approveCreditLimit', $customer)
    <form action="{{ route('customers.approve-credit-limit', $customer) }}" method="POST">
        @csrf
        <button type="submit">Approve Credit Limit</button>
    </form>
@endcan
```

### In API Resources

```php
class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Include actions user can perform
            'can' => [
                'view' => $request->user()->can('view', $this->resource),
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
                'approve_credit' => $request->user()->can('approveCreditLimit', $this->resource),
            ],
        ];
    }
}
```

### Using Gates

```php
use Illuminate\Support\Facades\Gate;

// Check if user can manage tenant
if (Gate::allows('manage-tenant')) {
    // User can manage tenant
}

// Check if user can access API
if (Gate::denies('access-api')) {
    abort(403);
}
```

## Required Permissions Setup

### Sales Module Permissions
```php
'customer.view', 'customer.create', 'customer.update', 'customer.delete',
'customer.approve-credit-limit', 'customer.view-financial-data', 
'customer.update-credit-limit', 'customer.merge', 'customer.export', 'customer.import',

'lead.view', 'lead.create', 'lead.update', 'lead.delete',
'lead.convert-to-customer', 'lead.assign', 'lead.qualify', 'lead.disqualify',

'sales-order.view', 'sales-order.create', 'sales-order.update', 'sales-order.delete',
'sales-order.confirm', 'sales-order.cancel', 'sales-order.approve',
'sales-order.create-invoice', 'sales-order.schedule-delivery',
'sales-order.update-pricing', 'sales-order.apply-discount',
```

### Inventory Module Permissions
```php
'product.view', 'product.create', 'product.update', 'product.delete',
'product.activate', 'product.deactivate', 'product.update-pricing',
'product.update-stock', 'product.view-cost', 'product.duplicate', 'product.manage-variants',

'warehouse.view', 'warehouse.create', 'warehouse.update', 'warehouse.delete',
'warehouse.manage-stock', 'warehouse.perform-stock-count', 'warehouse.approve-stock-count',
'warehouse.transfer-stock', 'warehouse.view-reports', 'warehouse.configure-settings',

'stock-movement.view', 'stock-movement.create', 'stock-movement.update', 'stock-movement.delete',
'stock-movement.approve', 'stock-movement.reject', 'stock-movement.complete',
'stock-movement.cancel', 'stock-movement.reverse',
```

### Accounting Module Permissions
```php
'account.view', 'account.create', 'account.update', 'account.delete',
'account.mark-system-account', 'account.activate', 'account.deactivate',
'account.reconcile', 'account.view-balance', 'account.view-transactions',

'invoice.view', 'invoice.create', 'invoice.update', 'invoice.delete',
'invoice.send', 'invoice.mark-paid', 'invoice.mark-void', 'invoice.approve',
'invoice.cancel', 'invoice.apply-payment', 'invoice.send-reminder', 'invoice.create-credit-note',

'journal-entry.view', 'journal-entry.create', 'journal-entry.update', 'journal-entry.delete',
'journal-entry.post', 'journal-entry.reverse', 'journal-entry.approve', 'journal-entry.reject',
'journal-entry.submit', 'journal-entry.close-period',

'payment.view', 'payment.create', 'payment.update', 'payment.delete',
'payment.process', 'payment.reconcile', 'payment.approve', 'payment.reject',
'payment.cancel', 'payment.refund', 'payment.void', 'payment.allocate',
```

### HR Module Permissions
```php
'employee.view', 'employee.create', 'employee.update', 'employee.delete',
'employee.terminate', 'employee.reactivate', 'employee.view-salary', 'employee.update-salary',
'employee.view-performance-reviews', 'employee.promote', 'employee.transfer',
'employee.view-documents', 'employee.manage-documents',

'leave.view', 'leave.create', 'leave.update', 'leave.delete',
'leave.approve', 'leave.reject', 'leave.cancel', 'leave.view-balance',
'leave.override-balance', 'leave.submit',

'payroll.view', 'payroll.create', 'payroll.update', 'payroll.delete',
'payroll.process', 'payroll.approve', 'payroll.reject', 'payroll.finalize',
'payroll.reverse', 'payroll.recalculate', 'payroll.generate-report', 'payroll.export',

'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete',
'attendance.check-in', 'attendance.check-out', 'attendance.approve', 'attendance.reject',
'attendance.correct', 'attendance.override', 'attendance.view-reports', 'attendance.export',
```

### Procurement Module Permissions
```php
'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',
'supplier.rate', 'supplier.activate', 'supplier.deactivate', 'supplier.approve',
'supplier.blacklist', 'supplier.view-pricing', 'supplier.view-performance', 'supplier.export',

'purchase-requisition.view', 'purchase-requisition.create', 
'purchase-requisition.update', 'purchase-requisition.delete',
'purchase-requisition.approve', 'purchase-requisition.reject', 
'purchase-requisition.convert-to-po', 'purchase-requisition.submit',
'purchase-requisition.cancel', 'purchase-requisition.resubmit', 'purchase-requisition.assign',

'purchase-order.view', 'purchase-order.create', 'purchase-order.update', 'purchase-order.delete',
'purchase-order.send', 'purchase-order.confirm', 'purchase-order.receive', 'purchase-order.approve',
'purchase-order.cancel', 'purchase-order.create-invoice', 'purchase-order.return-goods',
'purchase-order.update-pricing', 'purchase-order.close',
```

## Registering Policies

Policies are automatically registered in `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    \Modules\Sales\Entities\Customer::class => \Modules\Sales\Policies\CustomerPolicy::class,
    \Modules\Sales\Entities\Lead::class => \Modules\Sales\Policies\LeadPolicy::class,
    // ... other policies
];
```

## Testing Policies

```php
use Tests\TestCase;
use App\Models\User;
use Modules\Sales\Entities\Customer;

class CustomerPolicyTest extends TestCase
{
    public function test_user_can_view_customer_in_same_tenant()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $this->assertTrue($user->can('view', $customer));
    }
    
    public function test_user_cannot_view_customer_in_different_tenant()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => 999]);
        
        $this->assertFalse($user->can('view', $customer));
    }
    
    public function test_manager_can_approve_credit_limit()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');
        $customer = Customer::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $this->assertTrue($user->can('approveCreditLimit', $customer));
    }
}
```

## Security Considerations

1. **Always check tenant isolation**: Every policy includes tenant isolation checks
2. **Super admin bypass**: Super admins bypass all permission checks
3. **Status-based restrictions**: Many operations restricted based on entity status
4. **Ownership checks**: Some operations only allowed by resource owners
5. **Role hierarchy**: Certain operations require specific roles
6. **Self-approval prevention**: Users cannot approve their own requests

## Extending Policies

To add custom abilities to a policy:

```php
class CustomerPolicy extends BasePolicy
{
    protected string $permissionPrefix = 'customer';
    
    public function customAbility($user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'custom-ability') &&
               $this->checkTenantIsolation($user, $customer) &&
               // Add your custom logic here
               $this->hasAnyRole($user, ['admin', 'manager']);
    }
}
```

## Best Practices

1. **Use descriptive ability names**: `approveCreditLimit` instead of `approve`
2. **Always check tenant isolation**: Use `checkTenantIsolation()` in all methods
3. **Combine permission and role checks**: For sensitive operations
4. **Add status checks**: Prevent invalid state transitions
5. **Override base methods when needed**: Add entity-specific logic
6. **Document complex logic**: Add PHPDoc comments explaining business rules
7. **Test thoroughly**: Write tests for all policy methods

## Troubleshooting

### Policy not found
Ensure the policy is registered in `AuthServiceProvider`:
```php
protected $policies = [
    YourModel::class => YourModelPolicy::class,
];
```

### Permission denied unexpectedly
1. Check if user has the required permission: `$user->hasPermissionTo('resource.action')`
2. Verify tenant isolation: Ensure resource belongs to user's tenant
3. Check role requirements: Some abilities require specific roles
4. Review entity status: Some operations restricted by status

### Super admin not bypassing checks
Ensure `Gate::before()` is defined in `AuthServiceProvider`:
```php
Gate::before(function ($user, $ability) {
    if ($user->hasRole('super-admin')) {
        return true;
    }
});
```

## Additional Resources

- [Laravel Authorization Documentation](https://laravel.com/docs/11.x/authorization)
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission/v6)
- [Clean Architecture Principles](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

## Summary

This authorization system provides:
- ✅ 17 comprehensive policies covering all major modules
- ✅ 150+ custom abilities beyond standard CRUD
- ✅ Multi-tenant isolation at every level
- ✅ Role-based and permission-based access control
- ✅ Status-based operation restrictions
- ✅ Owner and department-based authorization
- ✅ Extensible base policy for consistent behavior
- ✅ Security-first approach with defense in depth
