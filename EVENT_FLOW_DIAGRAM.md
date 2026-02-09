# Event-Driven Module Integration Flow

## Overview
This diagram shows how events enable loose coupling between modules through the event-driven architecture.

## Event Flow Diagrams

### 1. Inventory Stock Movement → Accounting Integration

```
┌─────────────────────────────────────────────────────────────────┐
│                    Inventory Module                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  StockMovement Created/Updated                                 │
│         │                                                       │
│         │ dispatch                                              │
│         ▼                                                       │
│  ┌──────────────────────────────┐                              │
│  │ StockMovementRecorded Event  │                              │
│  └──────────────────────────────┘                              │
│         │                                                       │
└─────────│───────────────────────────────────────────────────────┘
          │
          │ listened by
          ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Accounting Module                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────┐                      │
│  │ UpdateAccountingValueListener        │                      │
│  │ (queued, async)                      │                      │
│  └──────────────────────────────────────┘                      │
│         │                                                       │
│         ├─► Calculate inventory value change                   │
│         ├─► Get/Create Inventory Asset Account (1400)          │
│         ├─► Get/Create Contra Account (COGS/AP/Adj)            │
│         ├─► Create Journal Entry                               │
│         │   ├─► Entry Lines (Debit/Credit)                     │
│         │   └─► Double-entry bookkeeping                       │
│         └─► Update inventory valuation                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Accounting Flow:**
- **Stock Increase (Receipt):** Debit Inventory (1400), Credit AP/Payable (2100)
- **Stock Decrease (Issue):** Debit COGS (5000), Credit Inventory (1400)
- **Adjustment:** Debit/Credit Inventory (1400), Credit/Debit Adjustment Expense (6100)

### 2. Low Stock Alert Notification

```
┌─────────────────────────────────────────────────────────────────┐
│                    Inventory Module                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Stock Level Check (quantity < reorder_point)                  │
│         │                                                       │
│         │ dispatch                                              │
│         ▼                                                       │
│  ┌──────────────────────────────┐                              │
│  │   LowStockAlert Event        │                              │
│  └──────────────────────────────┘                              │
│         │                                                       │
│         │                                                       │
│         │ listened by (same module)                             │
│         ▼                                                       │
│  ┌──────────────────────────────────────┐                      │
│  │  StockLevelAlertListener             │                      │
│  │  (queued, async)                     │                      │
│  └──────────────────────────────────────┘                      │
│         │                                                       │
│         ├─► Log alert (product, warehouse, shortage)           │
│         ├─► Calculate shortage amount                          │
│         └─► Send notification (future: email/SMS)              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 3. Procurement Goods Receipt → Multi-Module Integration

```
┌─────────────────────────────────────────────────────────────────┐
│                 Procurement Module                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Goods Received from Supplier                                  │
│         │                                                       │
│         │ dispatch                                              │
│         ▼                                                       │
│  ┌──────────────────────────────┐                              │
│  │   GoodsReceived Event        │                              │
│  │   - goodsReceipt             │                              │
│  │   - lines (products, qty)    │                              │
│  └──────────────────────────────┘                              │
│         │                                                       │
└─────────┼───────────────────────────────────────────────────────┘
          │
          ├───────────────────┬─────────────────────────┐
          │                   │                         │
          ▼                   ▼                         ▼
┌─────────────────┐  ┌────────────────────┐  ┌─────────────────────┐
│  Inventory      │  │  Accounting        │  │  Other Modules      │
│  Module         │  │  Module            │  │  (Future)           │
└─────────────────┘  └────────────────────┘  └─────────────────────┘
          │                   │                         │
          ▼                   ▼                         ▼
┌──────────────────┐ ┌────────────────────┐ ┌─────────────────────┐
│UpdateStockOn     │ │CreateAPInvoice     │ │Future Listeners     │
│ReceiptListener   │ │Listener            │ │                     │
│(queued, async)   │ │(queued, async)     │ │                     │
└──────────────────┘ └────────────────────┘ └─────────────────────┘
          │                   │
          │                   │
  ┌───────┴────────┐  ┌──────┴────────┐
  ▼                ▼  ▼               ▼
Create Stock    Update  Create AP   Create AP
Movement        Stock   Invoice     Invoice
Records         Levels  Header      Lines
  │                │      │           │
  ├─ Product      │      ├─ Supplier │
  ├─ Quantity     │      ├─ Totals   │
  ├─ Unit Cost    │      ├─ Due Date │
  └─ Reference    │      └─ Currency │
                  │
            ┌─────┴──────┐
            ▼            ▼
        Find or      Calculate
        Create       Weighted
        Stock Level  Average Cost
```

**Inventory Flow:**
1. Create stock movement record (type: RECEIPT)
2. Find existing stock level or create new
3. Update quantity using weighted average cost
4. Update available quantity

**Accounting Flow:**
1. Calculate subtotal, tax, discount
2. Generate unique AP invoice number
3. Create invoice header
4. Create invoice lines from goods receipt lines
5. Set due date based on payment terms

### 4. HR Payroll Processing → Accounting Integration

```
┌─────────────────────────────────────────────────────────────────┐
│                        HR Module                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Payroll Calculated and Processed                              │
│         │                                                       │
│         │ dispatch                                              │
│         ▼                                                       │
│  ┌──────────────────────────────┐                              │
│  │  PayrollProcessed Event      │                              │
│  │  - payroll                   │                              │
│  │  - gross_salary              │                              │
│  │  - deductions                │                              │
│  │  - taxes                     │                              │
│  │  - net_salary                │                              │
│  └──────────────────────────────┘                              │
│         │                                                       │
└─────────│───────────────────────────────────────────────────────┘
          │
          │ listened by
          ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Accounting Module                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────┐                      │
│  │ CreatePayrollJournalListener         │                      │
│  │ (queued, async)                      │                      │
│  └──────────────────────────────────────┘                      │
│         │                                                       │
│         ├─► Create Journal Entry Header                        │
│         │                                                       │
│         ├─► DEBIT Entries:                                     │
│         │   ├─► Salary Expense (6200)                          │
│         │   ├─► Employer Tax Expense (6210)                    │
│         │   └─► Employee Benefits Expense (6220)               │
│         │                                                       │
│         └─► CREDIT Entries:                                    │
│             ├─► Employee Tax Payable (2200)                    │
│             ├─► Other Deductions (2200)                        │
│             ├─► Salaries Payable (2210)                        │
│             ├─► Employer Tax Payable (2200)                    │
│             └─► Employer Benefits Payable (2200)               │
│                                                                 │
│  Double-Entry Bookkeeping Balanced:                            │
│  Total Debits = Total Credits                                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Payroll Journal Entry Example:**
```
Entry: JE-PAY-20240209-0001
Period: 2024-01-01 to 2024-01-31

DEBIT:
  Salary Expense (6200).................... $10,000.00
  Employer Tax Expense (6210).............. $   800.00
  Employee Benefits Expense (6220)......... $   500.00
                                           ───────────
  Total Debits............................ $11,300.00

CREDIT:
  Employee Tax Payable (2200).............. $ 1,200.00
  Other Deductions (2200).................. $   300.00
  Salaries Payable (2210).................. $ 8,500.00
  Employer Tax Payable (2200).............. $   800.00
  Employer Benefits Payable (2200)......... $   500.00
                                           ───────────
  Total Credits........................... $11,300.00
```

### 5. Sales Order Confirmed → Multi-Module Integration

```
┌─────────────────────────────────────────────────────────────────┐
│                      Sales Module                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Sales Order Confirmed                                         │
│         │                                                       │
│         │ dispatch                                              │
│         ▼                                                       │
│  ┌──────────────────────────────┐                              │
│  │ SalesOrderConfirmed Event    │                              │
│  │ - salesOrder                 │                              │
│  │ - customer                   │                              │
│  │ - lines                      │                              │
│  └──────────────────────────────┘                              │
│         │                                                       │
└─────────┼───────────────────────────────────────────────────────┘
          │
          ├───────────────────┬─────────────────────────┐
          │                   │                         │
          ▼                   ▼                         ▼
┌─────────────────┐  ┌────────────────────┐  ┌─────────────────────┐
│  Accounting     │  │  Inventory         │  │  Sales Module       │
│  Module         │  │  Module            │  │  (Logging)          │
└─────────────────┘  └────────────────────┘  └─────────────────────┘
          │                   │                         │
          ▼                   ▼                         ▼
┌──────────────────┐ ┌────────────────────┐ ┌─────────────────────┐
│CreateAccounting  │ │ReserveStock        │ │LogSalesOrder        │
│EntryListener     │ │Listener            │ │Confirmation         │
│(queued, async)   │ │(queued, async)     │ │                     │
└──────────────────┘ └────────────────────┘ └─────────────────────┘
          │                   │                         │
  Create AR Invoice    Reserve Stock         Log Event
  + Invoice Lines      Movement Records      Details
```

## Event Architecture Benefits

### 1. Loose Coupling
- Modules don't directly depend on each other
- Event producers don't know about consumers
- Easy to add/remove listeners without changing emitters

### 2. Async Processing
- All listeners run in queue (background jobs)
- Non-blocking event dispatch
- Better performance and scalability

### 3. Reliability
- 3 retry attempts with exponential backoff
- Failed job tracking
- Transaction rollback on failure

### 4. Auditability
- Comprehensive logging at each step
- Event history for debugging
- Clear integration points

### 5. Extensibility
- Easy to add new listeners
- Can add multiple listeners to same event
- No modification to existing code

## Queue Processing Flow

```
Event Dispatched
      │
      ▼
┌──────────────┐
│ Queue System │ (Redis/Database)
└──────────────┘
      │
      ▼
┌──────────────┐
│ Queue Worker │ (php artisan queue:work)
└──────────────┘
      │
      ├─► Attempt 1 ──► Success ──► Done
      ├─► Attempt 1 ──► Fail ──► Wait 10s
      ├─► Attempt 2 ──► Fail ──► Wait 10s
      ├─► Attempt 3 ──► Fail ──► Failed Job Table
      │                            │
      │                            └─► Call failed() method
      │                                │
      │                                └─► Log permanent failure
      │
      └─► DB Transaction Rollback on Failure
```

## Module Dependencies (Through Events)

```
┌─────────────┐
│   Sales     │──────┐
└─────────────┘      │
                     ▼
┌─────────────┐   ┌──────────────┐
│Procurement  │──→│  Inventory   │
└─────────────┘   └──────────────┘
      │                  │
      ├──────────────────┤
      │                  │
      ▼                  ▼
┌──────────────────────────┐
│      Accounting          │
└──────────────────────────┘
      ▲
      │
┌─────────────┐
│     HR      │
└─────────────┘

Legend:
──→ : Event-driven integration (loose coupling)
```

## Summary

The event-driven architecture provides:
- ✅ Loose coupling between modules
- ✅ Async processing for better performance
- ✅ Automatic retry logic for reliability
- ✅ Transaction management for data consistency
- ✅ Comprehensive logging for auditability
- ✅ Easy extensibility for future features
- ✅ Clean Architecture compliance
- ✅ SOLID principles adherence

All module integrations happen through events, ensuring no direct dependencies and maximum flexibility.
