# Next Steps Implementation Guide

This document provides a detailed action plan for completing the remaining 5% of the kv-saas-crm-erp system implementation.

---

## Overview

**Current Status**: 95% Complete  
**Remaining Work**: ~14 weeks to full production readiness  
**Critical Path**: Test Coverage → Business Logic → Frontend → Production

---

## Phase 1: Critical Test Coverage (Weeks 1-2)

### Priority: CRITICAL
**Estimated Effort**: 10-12 days  
**Target**: 80%+ code coverage

### 1.1 Inventory Module Tests (3-4 days)

**Unit Tests (20+ tests)**:
```
Tests/Unit/ProductTest.php               - 10 tests
Tests/Unit/WarehouseTest.php            - 5 tests
Tests/Unit/StockLevelTest.php           - 5 tests
```

**Service Tests (10+ tests)**:
```
Tests/Unit/ProductServiceTest.php       - 6 tests
Tests/Unit/InventoryServiceTest.php     - 4 tests
```

**Feature Tests (10+ tests)**:
```
Tests/Feature/ProductApiTest.php        - 5 tests
Tests/Feature/StockMovementApiTest.php  - 5 tests
```

**Reference Patterns**: Use `Modules/Sales/Tests/` as template

### 1.2 HR Module Tests (4-5 days)

**Unit Tests (25+ tests)**:
```
Tests/Unit/EmployeeTest.php             - 8 tests
Tests/Unit/AttendanceTest.php           - 6 tests
Tests/Unit/LeaveTest.php                - 6 tests
Tests/Unit/PayrollTest.php              - 5 tests
```

**Service Tests (15+ tests)**:
```
Tests/Unit/EmployeeServiceTest.php      - 5 tests
Tests/Unit/AttendanceServiceTest.php    - 5 tests
Tests/Unit/PayrollServiceTest.php       - 5 tests
```

**Feature Tests (10+ tests)**:
```
Tests/Feature/EmployeeApiTest.php       - 5 tests
Tests/Feature/LeaveApiTest.php          - 5 tests
```

**Reference Patterns**: Use `Modules/Tenancy/Tests/` as template

### 1.3 Procurement Module Tests (3-4 days)

**Unit Tests (20+ tests)**:
```
Tests/Unit/SupplierTest.php             - 6 tests
Tests/Unit/PurchaseOrderTest.php        - 8 tests
Tests/Unit/PurchaseRequisitionTest.php  - 6 tests
```

**Service Tests (10+ tests)**:
```
Tests/Unit/SupplierServiceTest.php      - 5 tests
Tests/Unit/PurchaseOrderServiceTest.php - 5 tests
```

**Feature Tests (10+ tests)**:
```
Tests/Feature/SupplierApiTest.php       - 5 tests
Tests/Feature/PurchaseOrderApiTest.php  - 5 tests
```

**Reference Patterns**: Use `Modules/Sales/Tests/` as template

### 1.4 Test Execution Checklist

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific module tests
./vendor/bin/phpunit --testsuite=Inventory
./vendor/bin/phpunit --testsuite=HR
./vendor/bin/phpunit --testsuite=Procurement

# Check coverage
./vendor/bin/phpunit --coverage-html build/coverage
```

**Success Criteria**:
- ✅ All tests pass
- ✅ 80%+ code coverage
- ✅ No failing tests
- ✅ No skipped tests

---

## Phase 2: Critical Business Logic (Weeks 3-4)

### Priority: HIGH
**Estimated Effort**: 7-10 days

### 2.1 HR Payroll Calculation Engine (3-5 days)

**Location**: `Modules/HR/Services/PayrollService.php`

**Implementation Steps**:

1. **Calculate Base Salary**
```php
public function calculateBaseSalary(Employee $employee, Carbon $periodStart, Carbon $periodEnd): float
{
    // Get employee's salary from position
    // Calculate based on payment frequency (monthly, bi-weekly, weekly)
    // Handle pro-rated salaries for partial periods
}
```

2. **Calculate Deductions**
```php
public function calculateDeductions(Employee $employee, float $grossPay): array
{
    // Tax deductions (based on tax brackets)
    // Insurance deductions
    // Retirement contributions
    // Other deductions
    return [
        'tax' => $taxAmount,
        'insurance' => $insuranceAmount,
        'retirement' => $retirementAmount,
    ];
}
```

3. **Calculate Benefits**
```php
public function calculateBenefits(Employee $employee, Carbon $period): array
{
    // Allowances (housing, transportation, meal)
    // Bonuses
    // Overtime pay
    return [
        'allowances' => $allowanceAmount,
        'bonuses' => $bonusAmount,
        'overtime' => $overtimeAmount,
    ];
}
```

4. **Generate Payslip**
```php
public function generatePayslip(Employee $employee, Carbon $period): Payroll
{
    $baseSalary = $this->calculateBaseSalary($employee, $period);
    $benefits = $this->calculateBenefits($employee, $period);
    $grossPay = $baseSalary + array_sum($benefits);
    $deductions = $this->calculateDeductions($employee, $grossPay);
    $netPay = $grossPay - array_sum($deductions);
    
    return Payroll::create([
        'employee_id' => $employee->id,
        'period_start' => $period->startOfMonth(),
        'period_end' => $period->endOfMonth(),
        'gross_pay' => $grossPay,
        'deductions' => $deductions,
        'net_pay' => $netPay,
        'status' => 'draft',
    ]);
}
```

**Testing**:
```php
Tests/Unit/PayrollCalculationTest.php    - 15+ tests
Tests/Feature/PayrollProcessingTest.php  - 10+ tests
```

### 2.2 Leave Approval Workflow (2-3 days)

**Location**: `Modules/HR/Services/LeaveService.php`

**Implementation Steps**:

1. **Submit Leave Request**
```php
public function submitLeaveRequest(array $data): Leave
{
    $leave = Leave::create([
        'employee_id' => $data['employee_id'],
        'leave_type_id' => $data['leave_type_id'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'reason' => $data['reason'],
        'status' => 'pending',
    ]);
    
    // Notify manager
    event(new LeaveRequestSubmitted($leave));
    
    return $leave;
}
```

2. **Approve Leave**
```php
public function approveLeave(Leave $leave, User $approver): Leave
{
    // Check if user has permission to approve
    $this->authorize('approve', $leave);
    
    // Update status
    $leave->status = 'approved';
    $leave->approved_by = $approver->id;
    $leave->approved_at = now();
    $leave->save();
    
    // Notify employee
    event(new LeaveApproved($leave));
    
    return $leave;
}
```

3. **Reject Leave**
```php
public function rejectLeave(Leave $leave, User $approver, string $reason): Leave
{
    // Check if user has permission to reject
    $this->authorize('reject', $leave);
    
    // Update status
    $leave->status = 'rejected';
    $leave->rejected_by = $approver->id;
    $leave->rejected_at = now();
    $leave->rejection_reason = $reason;
    $leave->save();
    
    // Notify employee
    event(new LeaveRejected($leave));
    
    return $leave;
}
```

**Testing**:
```php
Tests/Unit/LeaveWorkflowTest.php         - 12+ tests
Tests/Feature/LeaveApprovalApiTest.php   - 8+ tests
```

### 2.3 Procurement Approval Workflow (2-3 days)

**Location**: `Modules/Procurement/Services/PurchaseRequisitionService.php`

**Implementation Steps**:

1. **Submit Requisition**
```php
public function submitRequisition(array $data): PurchaseRequisition
{
    $requisition = PurchaseRequisition::create([
        'requisition_number' => $this->generateRequisitionNumber(),
        'requested_by' => $data['requested_by'],
        'department_id' => $data['department_id'],
        'status' => 'pending',
    ]);
    
    // Add line items
    foreach ($data['items'] as $item) {
        $requisition->lines()->create($item);
    }
    
    // Notify approvers
    event(new RequisitionSubmitted($requisition));
    
    return $requisition;
}
```

2. **Approve Requisition**
```php
public function approveRequisition(PurchaseRequisition $requisition, User $approver): PurchaseRequisition
{
    // Check approval level
    $approvalLevel = $this->getRequiredApprovalLevel($requisition->total);
    
    // Check if user can approve at this level
    if (!$this->canApproveAtLevel($approver, $approvalLevel)) {
        throw new UnauthorizedException('Insufficient approval authority');
    }
    
    // Update status
    $requisition->status = 'approved';
    $requisition->approved_by = $approver->id;
    $requisition->approved_at = now();
    $requisition->save();
    
    // Notify requester
    event(new RequisitionApproved($requisition));
    
    return $requisition;
}
```

3. **Convert to Purchase Order**
```php
public function convertToPurchaseOrder(PurchaseRequisition $requisition, array $data): PurchaseOrder
{
    // Verify requisition is approved
    if ($requisition->status !== 'approved') {
        throw new \Exception('Only approved requisitions can be converted to purchase orders');
    }
    
    // Create purchase order
    $purchaseOrder = PurchaseOrder::create([
        'order_number' => $this->generateOrderNumber(),
        'requisition_id' => $requisition->id,
        'supplier_id' => $data['supplier_id'],
        'order_date' => now(),
        'status' => 'draft',
    ]);
    
    // Copy line items
    foreach ($requisition->lines as $line) {
        $purchaseOrder->lines()->create([
            'product_id' => $line->product_id,
            'quantity' => $line->quantity,
            'unit_price' => $data['prices'][$line->product_id] ?? 0,
        ]);
    }
    
    // Update requisition status
    $requisition->status = 'converted';
    $requisition->save();
    
    return $purchaseOrder;
}
```

**Testing**:
```php
Tests/Unit/RequisitionWorkflowTest.php   - 12+ tests
Tests/Feature/RequisitionApprovalTest.php - 10+ tests
```

---

## Phase 3: Email Notifications (Week 3)

### Priority: MEDIUM
**Estimated Effort**: 2-3 days

### 3.1 Invoice Email Notifications

**Location**: `Modules/Accounting/Services/InvoiceService.php`

**Implementation**:

1. **Create Invoice Mail Class**
```php
// Modules/Accounting/Mail/InvoiceSentMail.php
use Illuminate\Mail\Mailable;

class InvoiceSentMail extends Mailable
{
    public function __construct(public Invoice $invoice) {}
    
    public function build()
    {
        return $this->subject("Invoice {$this->invoice->invoice_number}")
                    ->view('accounting::emails.invoice-sent')
                    ->attach($this->generatePdfPath());
    }
}
```

2. **Update InvoiceService**
```php
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Mail\InvoiceSentMail;

public function sendInvoice(Invoice $invoice): Invoice
{
    // ... existing code ...
    
    // Send email notification (replacing TODO)
    Mail::to($invoice->customer->email)
        ->send(new InvoiceSentMail($invoice));
    
    $this->logInfo('Invoice sent via email', [
        'invoice_id' => $invoice->id,
        'customer_email' => $invoice->customer->email,
    ]);
    
    return $invoice;
}
```

3. **Create Email Template**
```blade
{{-- Modules/Accounting/Resources/views/emails/invoice-sent.blade.php --}}
<h1>Invoice {{ $invoice->invoice_number }}</h1>
<p>Dear {{ $invoice->customer->name }},</p>
<p>Please find attached invoice for {{ $invoice->total }} {{ $invoice->currency }}.</p>
<p>Due date: {{ $invoice->due_date->format('Y-m-d') }}</p>
```

**Testing**:
```php
Tests/Feature/InvoiceEmailTest.php       - 5+ tests
```

### 3.2 Stock Alert Notifications

**Location**: `Modules/Inventory/Listeners/StockLevelAlertListener.php`

**Implementation**:

1. **Create Notification Class**
```php
// Modules/Inventory/Notifications/LowStockNotification.php
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    public function __construct(public StockLevel $stockLevel) {}
    
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Low Stock Alert: {$this->stockLevel->product->name}")
            ->line("Stock level for {$this->stockLevel->product->name} is below reorder point.")
            ->line("Current quantity: {$this->stockLevel->quantity}")
            ->line("Reorder point: {$this->stockLevel->reorder_point}")
            ->action('View Product', url("/inventory/products/{$this->stockLevel->product->id}"));
    }
}
```

2. **Update Listener**
```php
use Modules\Inventory\Notifications\LowStockNotification;

public function handle(LowStockAlert $event): void
{
    // ... existing code ...
    
    // Get inventory managers
    $inventoryManagers = User::role('inventory-manager')->get();
    
    // Send notifications (replacing TODO)
    Notification::send($inventoryManagers, new LowStockNotification($stockLevel));
    
    // ... existing code ...
}
```

---

## Phase 4: Frontend Implementation (Weeks 5-8)

### Priority: HIGH
**Estimated Effort**: 3-4 weeks

### 4.1 Vue.js 3 Setup (Week 5)

**Steps**:
1. Install dependencies (Vue 3, Vue Router, native only)
2. Configure Vite for Vue
3. Set up project structure
4. Create base layouts and components

**Directory Structure**:
```
resources/
  js/
    app.js                  # Main entry point
    router/
      index.js              # Vue Router config
    composables/            # Reusable logic
      useAuth.js
      useApi.js
      useTenancy.js
    components/
      common/               # Shared components
      layouts/              # Layout components
    views/
      dashboard/
      sales/
      inventory/
      accounting/
      hr/
      procurement/
```

### 4.2 Core Components (Week 6)

**Components to Create**:
- DataTable.vue (sortable, filterable, paginated)
- FormInput.vue (text, number, date, select)
- Modal.vue (using Teleport)
- Alert.vue (success, error, warning)
- Loading.vue (spinner, skeleton)
- Card.vue (container)
- Button.vue (primary, secondary, danger)

**Example**:
```vue
<!-- resources/js/components/common/DataTable.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  columns: Array<{ key: string; label: string; sortable?: boolean }>
  data: Array<Record<string, any>>
  loading?: boolean
}

const props = defineProps<Props>()
const sortKey = ref('')
const sortOrder = ref<'asc' | 'desc'>('asc')

const sortedData = computed(() => {
  // Sorting logic
})
</script>

<template>
  <!-- Table implementation -->
</template>
```

### 4.3 Module Views (Week 7)

**Views to Create**:
- Dashboard view
- Sales/Customer list and detail views
- Inventory/Product views
- Accounting/Invoice views
- HR/Employee views
- Procurement/Purchase Order views

### 4.4 Integration & Testing (Week 8)

**Tasks**:
- API integration with backend
- Authentication flow
- Multi-language support
- Responsive design testing
- Cross-browser testing

---

## Phase 5: API Documentation (Week 9)

### Priority: MEDIUM
**Estimated Effort**: 5-7 days

### 5.1 OpenAPI Specification

**For Each Module**:

1. **Create OpenAPI YAML**
```yaml
# docs/api/modules/tenancy.yaml
paths:
  /api/v1/tenants:
    get:
      summary: List all tenants
      tags: [Tenancy]
      security:
        - sanctum: []
      parameters:
        - name: page
          in: query
          schema:
            type: integer
      responses:
        200:
          description: List of tenants
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Tenant'
```

2. **Document All Endpoints**
- List, Create, Read, Update, Delete
- Search and filter
- Custom actions (approve, reject, etc.)

3. **Add Examples**
- Request examples
- Response examples
- Error examples

### 5.2 Postman Collections

**Create Collections**:
- Tenancy API
- Sales API
- Inventory API
- Accounting API
- HR API
- Procurement API

**Include**:
- Authentication setup
- Environment variables
- Example requests
- Tests for responses

---

## Phase 6: Production Readiness (Weeks 10-14)

### Priority: CRITICAL
**Estimated Effort**: 4-6 weeks

### 6.1 Performance Testing (Week 10)

**Tools**: Apache Bench, k6, Laravel Telescope

**Tests**:
- API endpoint response times
- Database query optimization
- Cache effectiveness
- Queue processing times
- Concurrent user load

**Success Criteria**:
- API response < 200ms (p95)
- < 10 database queries per request
- Support 1000+ concurrent users

### 6.2 Security Audit (Week 11)

**Tests**:
- SQL injection attempts
- XSS vulnerability scanning
- CSRF protection verification
- Authentication bypass attempts
- Authorization policy testing
- Rate limiting verification

**Tools**:
- OWASP ZAP
- Burp Suite
- Laravel Security Checker

### 6.3 Load Testing (Week 12)

**Scenarios**:
- Normal load (100 users)
- Peak load (1000 users)
- Stress test (5000 users)
- Spike test (sudden traffic increase)

**Metrics**:
- Response times
- Error rates
- Resource utilization
- Database performance

### 6.4 CI/CD Pipeline (Week 13)

**Components**:
```yaml
# .github/workflows/ci.yml
name: CI Pipeline

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: ./vendor/bin/phpunit
      - name: Code style
        run: ./vendor/bin/pint --test
```

**Deployment Pipeline**:
- Build Docker image
- Push to registry
- Deploy to staging
- Run smoke tests
- Deploy to production

### 6.5 Documentation Review (Week 14)

**Update**:
- README.md
- API documentation
- Deployment guides
- Operations runbooks
- Troubleshooting guides

---

## Success Criteria

### Phase 1: Test Coverage ✅
- [ ] Inventory: 40+ tests passing
- [ ] HR: 50+ tests passing
- [ ] Procurement: 40+ tests passing
- [ ] Overall coverage: 80%+

### Phase 2: Business Logic ✅
- [ ] Payroll calculation functional
- [ ] Leave approval workflow working
- [ ] Procurement approval workflow working
- [ ] All business logic tested

### Phase 3: Notifications ✅
- [ ] Invoice emails sending
- [ ] Stock alert notifications working
- [ ] Email templates created

### Phase 4: Frontend ✅
- [ ] Vue.js 3 SPA functional
- [ ] All modules have UI
- [ ] Responsive design
- [ ] Multi-language support

### Phase 5: Documentation ✅
- [ ] OpenAPI specs complete
- [ ] Postman collections created
- [ ] API examples documented

### Phase 6: Production ✅
- [ ] Performance targets met
- [ ] Security audit passed
- [ ] Load tests successful
- [ ] CI/CD pipeline operational
- [ ] Documentation complete

---

## Timeline Summary

| Phase | Duration | Priority | Status |
|-------|----------|----------|--------|
| Test Coverage | 2 weeks | CRITICAL | Not Started |
| Business Logic | 2 weeks | HIGH | Not Started |
| Notifications | 3 days | MEDIUM | Not Started |
| Frontend | 4 weeks | HIGH | Not Started |
| API Docs | 1 week | MEDIUM | Not Started |
| Production | 5 weeks | CRITICAL | Not Started |
| **Total** | **14 weeks** | - | **5% Remaining** |

---

## Risk Mitigation

### High-Risk Items
1. **Payroll Calculation**: Complex business logic
   - Mitigation: Start early, extensive testing
   
2. **Frontend Implementation**: Large scope
   - Mitigation: Phased rollout, component reuse

3. **Load Testing**: May reveal performance issues
   - Mitigation: Early testing, iterative optimization

### Medium-Risk Items
1. **Approval Workflows**: Complex state machines
   - Mitigation: Clear state diagrams, comprehensive tests

2. **Email Notifications**: Delivery reliability
   - Mitigation: Queue system, retry logic, logging

---

## Resource Requirements

### Development Team
- 2-3 Backend Developers
- 1-2 Frontend Developers
- 1 QA Engineer
- 1 DevOps Engineer (part-time)

### Infrastructure
- Development environment
- Staging environment
- CI/CD pipeline
- Monitoring tools
- Load testing tools

---

**End of Implementation Guide**

*Last Updated: February 9, 2026*
