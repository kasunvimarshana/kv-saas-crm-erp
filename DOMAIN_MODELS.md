# Domain Models Reference

## Overview

This document provides detailed specifications of the core domain models, entities, value objects, and their relationships within the kv-saas-crm-erp system.

## Naming Conventions

- **Entities**: PascalCase (e.g., `Customer`, `SalesOrder`)
- **Value Objects**: PascalCase (e.g., `Money`, `Address`)
- **Properties**: camelCase (e.g., `firstName`, `emailAddress`)
- **Collections**: Plural form (e.g., `orderLines`, `addresses`)

## Common Base Patterns

### Entity Base
```
Entity (Abstract):
  - id: UUID (Primary Key)
  - tenantId: UUID (Multi-tenancy)
  - createdAt: Timestamp
  - createdBy: UUID (User reference)
  - updatedAt: Timestamp
  - updatedBy: UUID (User reference)
  - version: Integer (Optimistic locking)
  - isDeleted: Boolean (Soft delete)
```

### Aggregate Root Base
```
AggregateRoot extends Entity:
  - domainEvents: List<DomainEvent>
  - addDomainEvent(event: DomainEvent)
  - clearDomainEvents()
```

### Value Object Base
```
ValueObject (Abstract):
  - equals(other: ValueObject): Boolean
  - hashCode(): Integer
  - immutable: True
```

## Sales & CRM Domain

### Customer (Aggregate Root)

**Purpose**: Represents a customer entity in the system

**Properties**:
```
Customer extends AggregateRoot:
  - customerNumber: String (Unique, Auto-generated)
  - type: CustomerType (Individual, Business)
  - name: String
  - legalName: String (for Business)
  - taxId: String
  - emailAddress: EmailAddress (Value Object)
  - phoneNumber: PhoneNumber (Value Object)
  - website: URL
  - primaryContact: ContactPerson (Value Object)
  - billingAddress: Address (Value Object)
  - shippingAddresses: List<Address> (Value Objects)
  - paymentTerms: PaymentTerms (Value Object)
  - creditLimit: Money (Value Object)
  - currentBalance: Money (Value Object)
  - status: CustomerStatus (Active, Inactive, Blocked)
  - assignedSalesperson: Employee (Reference)
  - customFields: Map<String, Any>
```

**Relationships**:
- Has many: Leads, Opportunities, SalesOrders, Invoices
- Belongs to: Salesperson (Employee)
- Belongs to: Organization, Branch

**Business Rules**:
- Customer number must be unique within tenant
- Business customers must have legal name and tax ID
- Credit limit cannot be negative
- Cannot delete customer with outstanding balance

**Domain Events**:
- CustomerCreated
- CustomerUpdated
- CustomerStatusChanged
- CreditLimitExceeded

### Lead (Entity)

**Purpose**: Represents a potential customer opportunity

**Properties**:
```
Lead extends Entity:
  - leadNumber: String (Unique)
  - source: LeadSource (Website, Referral, Marketing, Cold Call)
  - firstName: String
  - lastName: String
  - companyName: String
  - title: String
  - emailAddress: EmailAddress
  - phoneNumber: PhoneNumber
  - address: Address
  - status: LeadStatus (New, Contacted, Qualified, Converted, Lost)
  - qualificationScore: Integer (0-100)
  - estimatedValue: Money
  - estimatedCloseDate: Date
  - notes: Text
  - assignedTo: Employee
  - convertedToCustomer: Customer (nullable)
  - convertedAt: Timestamp (nullable)
```

**Relationships**:
- Belongs to: Customer (when converted)
- Assigned to: Employee
- Can become: Opportunity

**Business Rules**:
- Lead can only be converted once
- Qualified leads can be converted to opportunities
- Lost leads cannot be converted

**Domain Events**:
- LeadCreated
- LeadQualified
- LeadConverted
- LeadLost

### Opportunity (Entity)

**Purpose**: Qualified sales opportunity with potential for revenue

**Properties**:
```
Opportunity extends Entity:
  - opportunityNumber: String (Unique)
  - name: String
  - description: Text
  - customer: Customer
  - leadSource: Lead (nullable)
  - stage: OpportunityStage (Prospecting, Qualification, Proposal, Negotiation, Closed Won, Closed Lost)
  - probability: Percentage (0-100)
  - amount: Money
  - expectedCloseDate: Date
  - actualCloseDate: Date (nullable)
  - products: List<OpportunityProduct>
  - competitors: List<String>
  - winLossReason: String (nullable)
  - assignedTo: Employee
  - notes: Text
```

**Relationships**:
- Belongs to: Customer
- Originated from: Lead
- Can generate: Quote, SalesOrder
- Assigned to: Employee

**Business Rules**:
- Probability must be between 0 and 100
- Closed opportunities must have actual close date
- Won opportunities must have probability = 100
- Lost opportunities must have win/loss reason

**Domain Events**:
- OpportunityCreated
- OpportunityStageChanged
- OpportunityWon
- OpportunityLost

### Quote (Entity)

**Purpose**: Formal price proposal to customer

**Properties**:
```
Quote extends Entity:
  - quoteNumber: String (Unique)
  - version: Integer
  - customer: Customer
  - opportunity: Opportunity (nullable)
  - expiryDate: Date
  - status: QuoteStatus (Draft, Sent, Accepted, Rejected, Expired)
  - subtotal: Money
  - discountAmount: Money
  - discountPercentage: Percentage
  - taxAmount: Money
  - totalAmount: Money
  - currency: CurrencyCode
  - exchangeRate: Decimal
  - terms: Text
  - notes: Text
  - lines: List<QuoteLine> (Value Objects)
  - acceptedBy: String (nullable)
  - acceptedAt: Timestamp (nullable)
  - rejectionReason: String (nullable)
```

**Relationships**:
- Belongs to: Customer
- Related to: Opportunity
- Can convert to: SalesOrder

**Business Rules**:
- Expired quotes cannot be accepted
- Accepted quotes can be converted to orders
- Totals must be calculated correctly
- Cannot modify accepted/rejected quotes

**Domain Events**:
- QuoteCreated
- QuoteSent
- QuoteAccepted
- QuoteRejected
- QuoteExpired

### SalesOrder (Aggregate Root)

**Purpose**: Customer order for products/services

**Properties**:
```
SalesOrder extends AggregateRoot:
  - orderNumber: String (Unique)
  - customer: Customer
  - quote: Quote (nullable)
  - orderDate: Date
  - requiredDate: Date
  - promisedDate: Date
  - status: OrderStatus (Draft, Confirmed, In Progress, Shipped, Delivered, Cancelled)
  - paymentStatus: PaymentStatus (Unpaid, Partial, Paid)
  - fulfillmentStatus: FulfillmentStatus (Pending, Partial, Fulfilled)
  - subtotal: Money
  - discountAmount: Money
  - taxAmount: Money
  - shippingAmount: Money
  - totalAmount: Money
  - currency: CurrencyCode
  - billingAddress: Address
  - shippingAddress: Address
  - paymentTerms: PaymentTerms
  - lines: List<OrderLine> (Entities within aggregate)
  - notes: Text
  - internalNotes: Text
```

**Relationships**:
- Belongs to: Customer
- Created from: Quote
- Generates: Invoice, Shipment
- Has many: OrderLines

**Business Rules**:
- Cannot modify confirmed orders without approval
- Cannot cancel shipped orders
- All lines must be available before fulfillment
- Total must match sum of lines plus taxes and shipping

**Domain Events**:
- OrderCreated
- OrderConfirmed
- OrderFulfilled
- OrderShipped
- OrderDelivered
- OrderCancelled

### OrderLine (Entity within SalesOrder Aggregate)

**Properties**:
```
OrderLine extends Entity:
  - lineNumber: Integer
  - product: Product
  - description: String
  - quantity: Quantity (Value Object)
  - unitPrice: Money
  - discountPercentage: Percentage
  - discountAmount: Money
  - taxRate: Percentage
  - taxAmount: Money
  - lineTotal: Money
  - notes: Text
```

**Business Rules**:
- Quantity must be positive
- Line total = (quantity * unitPrice - discount) + tax
- Cannot reference deleted products

## Inventory Domain

### Product (Aggregate Root)

**Purpose**: Represents a product or service offered

**Properties**:
```
Product extends AggregateRoot:
  - productNumber: String (Unique)
  - sku: String (Unique)
  - barcode: String (Unique, nullable)
  - name: String
  - description: Text
  - type: ProductType (Physical, Service, Digital)
  - category: ProductCategory
  - unitOfMeasure: UnitOfMeasure
  - alternativeUnits: List<UnitConversion>
  - cost: Money
  - listPrice: Money
  - salesPrice: Money
  - minPrice: Money
  - weight: Measurement
  - dimensions: Dimensions (Value Object)
  - isActive: Boolean
  - isSaleable: Boolean
  - isPurchaseable: Boolean
  - isStockable: Boolean
  - trackInventory: Boolean
  - reorderPoint: Quantity
  - reorderQuantity: Quantity
  - leadTime: Duration
  - suppliers: List<ProductSupplier>
  - customFields: Map<String, Any>
```

**Relationships**:
- Belongs to: ProductCategory
- Has many: StockLevels, PriceLists
- Related to: Suppliers through ProductSupplier

**Business Rules**:
- SKU must be unique within tenant
- Sales price cannot be less than min price
- Physical products must track inventory
- Reorder point must be less than reorder quantity

**Domain Events**:
- ProductCreated
- ProductUpdated
- ProductPriceChanged
- ProductDiscontinued

### StockLevel (Entity)

**Purpose**: Current inventory level for a product at a location

**Properties**:
```
StockLevel extends Entity:
  - product: Product
  - warehouse: Warehouse
  - location: Location (nullable)
  - quantityOnHand: Quantity
  - quantityReserved: Quantity
  - quantityAvailable: Quantity (calculated)
  - quantityOnOrder: Quantity
  - lastStockTake: Date
  - minimumLevel: Quantity
  - maximumLevel: Quantity
```

**Relationships**:
- Belongs to: Product, Warehouse, Location
- Modified by: StockMovements

**Business Rules**:
- Available = OnHand - Reserved
- Cannot go negative (unless allowed by settings)
- Alert when below minimum level
- One stock level per product-warehouse-location combination

**Domain Events**:
- StockLevelUpdated
- LowStockAlert
- StockReplenished
- StockDepleted

### StockMovement (Entity)

**Purpose**: Records all stock transactions

**Properties**:
```
StockMovement extends Entity:
  - movementNumber: String (Unique)
  - type: MovementType (Receipt, Issue, Adjustment, Transfer)
  - product: Product
  - fromWarehouse: Warehouse (nullable)
  - toWarehouse: Warehouse (nullable)
  - fromLocation: Location (nullable)
  - toLocation: Location (nullable)
  - quantity: Quantity
  - unitCost: Money
  - totalCost: Money
  - reason: String
  - referenceType: String (PurchaseOrder, SalesOrder, etc.)
  - referenceId: UUID
  - lotNumber: String (nullable)
  - serialNumber: String (nullable)
  - movementDate: Date
  - notes: Text
```

**Relationships**:
- References: Product, Warehouse, Location
- Links to: Source documents (Orders, Receipts, etc.)

**Business Rules**:
- Movements must balance (quantity in = quantity out for transfers)
- Cannot move more than available stock
- Must specify valid from/to locations
- Serial tracked items must have serial numbers

**Domain Events**:
- StockReceived
- StockIssued
- StockAdjusted
- StockTransferred

### Warehouse (Aggregate Root)

**Purpose**: Physical storage facility

**Properties**:
```
Warehouse extends AggregateRoot:
  - warehouseCode: String (Unique)
  - name: String
  - type: WarehouseType (Distribution, Retail, Manufacturing)
  - address: Address
  - organization: Organization
  - manager: Employee
  - operatingHours: OperatingSchedule
  - capacity: Measurement
  - utilizationPercentage: Percentage
  - isActive: Boolean
  - locations: List<Location> (Entities within aggregate)
```

**Relationships**:
- Belongs to: Organization
- Managed by: Employee
- Contains: Locations, StockLevels

**Business Rules**:
- Warehouse code must be unique
- Must have at least one location
- Cannot delete warehouse with stock

**Domain Events**:
- WarehouseCreated
- WarehouseActivated
- WarehouseDeactivated
- CapacityExceeded

## Accounting Domain

### Account (Aggregate Root)

**Purpose**: General ledger account

**Properties**:
```
Account extends AggregateRoot:
  - accountNumber: String (Unique)
  - name: String
  - type: AccountType (Asset, Liability, Equity, Revenue, Expense)
  - subType: String
  - parentAccount: Account (nullable)
  - currency: CurrencyCode
  - balance: Money
  - debitBalance: Money
  - creditBalance: Money
  - isActive: Boolean
  - allowManualEntry: Boolean
  - reconciliationRequired: Boolean
  - lastReconciliationDate: Date
```

**Relationships**:
- Parent-Child hierarchy
- Has many: JournalEntries

**Business Rules**:
- Account number must be unique within tenant
- Account hierarchy must be consistent with types
- Cannot delete account with transactions
- Debit accounts: Asset, Expense
- Credit accounts: Liability, Equity, Revenue

**Domain Events**:
- AccountCreated
- AccountBalanceChanged
- AccountReconciled

### JournalEntry (Aggregate Root)

**Purpose**: Double-entry accounting transaction

**Properties**:
```
JournalEntry extends AggregateRoot:
  - entryNumber: String (Unique)
  - date: Date
  - period: FiscalPeriod
  - type: JournalType (General, Sales, Purchase, Payment, Receipt)
  - status: EntryStatus (Draft, Posted, Reversed)
  - description: String
  - reference: String
  - currency: CurrencyCode
  - exchangeRate: Decimal
  - totalDebit: Money
  - totalCredit: Money
  - lines: List<JournalLine> (Entities within aggregate)
  - postedBy: Employee
  - postedAt: Timestamp
  - reversedBy: JournalEntry (nullable)
  - notes: Text
```

**Relationships**:
- Belongs to: FiscalPeriod
- Has many: JournalLines
- May reverse: Another JournalEntry

**Business Rules**:
- Total debits must equal total credits
- Cannot modify posted entries (must reverse)
- Must have at least 2 lines (debit and credit)
- Cannot post to closed periods

**Domain Events**:
- JournalEntryCreated
- JournalEntryPosted
- JournalEntryReversed

### Invoice (Aggregate Root)

**Purpose**: Bill to customer or from supplier

**Properties**:
```
Invoice extends AggregateRoot:
  - invoiceNumber: String (Unique)
  - type: InvoiceType (CustomerInvoice, SupplierInvoice, CreditNote, DebitNote)
  - customer: Customer (nullable)
  - supplier: Supplier (nullable)
  - invoiceDate: Date
  - dueDate: Date
  - status: InvoiceStatus (Draft, Sent, Partial, Paid, Overdue, Cancelled)
  - subtotal: Money
  - discountAmount: Money
  - taxAmount: Money
  - totalAmount: Money
  - amountPaid: Money
  - amountDue: Money
  - currency: CurrencyCode
  - exchangeRate: Decimal
  - paymentTerms: PaymentTerms
  - billingAddress: Address
  - lines: List<InvoiceLine>
  - payments: List<Payment>
  - journalEntry: JournalEntry
  - notes: Text
```

**Relationships**:
- Belongs to: Customer or Supplier
- Generated from: SalesOrder or PurchaseOrder
- Has many: InvoiceLines, Payments
- Creates: JournalEntry

**Business Rules**:
- Must have customer (AR) or supplier (AP)
- Due date must be after invoice date
- Cannot modify paid invoices
- Amount due = Total - Amount paid
- Status automatically calculated from payments

**Domain Events**:
- InvoiceCreated
- InvoiceSent
- InvoicePartiallyPaid
- InvoicePaid
- InvoiceOverdue
- InvoiceCancelled

### Payment (Entity)

**Purpose**: Payment received or made

**Properties**:
```
Payment extends Entity:
  - paymentNumber: String (Unique)
  - type: PaymentType (Receipt, Payment)
  - date: Date
  - amount: Money
  - currency: CurrencyCode
  - method: PaymentMethod (Cash, Check, Transfer, Card, Other)
  - reference: String
  - customer: Customer (nullable)
  - supplier: Supplier (nullable)
  - account: Account
  - invoice: Invoice (nullable)
  - allocations: List<PaymentAllocation>
  - status: PaymentStatus (Pending, Cleared, Reconciled, Void)
  - journalEntry: JournalEntry
  - notes: Text
```

**Relationships**:
- Belongs to: Customer or Supplier
- Applied to: Invoice(s)
- Posts to: Account
- Creates: JournalEntry

**Business Rules**:
- Amount must be positive
- Payment must be allocated to invoices
- Cannot void reconciled payments
- Total allocations cannot exceed amount

**Domain Events**:
- PaymentReceived
- PaymentMade
- PaymentReconciled
- PaymentVoided

## HR Domain

### Employee (Aggregate Root)

**Purpose**: Person employed by the organization

**Properties**:
```
Employee extends AggregateRoot:
  - employeeNumber: String (Unique)
  - firstName: String
  - lastName: String
  - fullName: String (computed)
  - dateOfBirth: Date
  - gender: Gender
  - nationalId: String
  - taxId: String
  - emailAddress: EmailAddress
  - phoneNumber: PhoneNumber
  - address: Address
  - emergencyContact: ContactPerson
  - department: Department
  - position: Position
  - manager: Employee (nullable)
  - hireDate: Date
  - terminationDate: Date (nullable)
  - employmentType: EmploymentType (FullTime, PartTime, Contract, Intern)
  - status: EmployeeStatus (Active, OnLeave, Suspended, Terminated)
  - salary: Salary (Value Object)
  - bankAccount: BankAccount
  - user: User (nullable)
  - customFields: Map<String, Any>
```

**Relationships**:
- Belongs to: Department, Position
- Reports to: Employee (Manager)
- Has many: Attendance, Leave, Payroll
- Links to: User account

**Business Rules**:
- Employee number must be unique
- Hire date cannot be in future
- Terminated employees cannot be assigned new tasks
- Manager must be in same or parent department

**Domain Events**:
- EmployeeHired
- EmployeePromoted
- EmployeeTransferred
- EmployeeTerminated

### Department (Entity)

**Purpose**: Organizational unit

**Properties**:
```
Department extends Entity:
  - code: String (Unique)
  - name: String
  - description: Text
  - parentDepartment: Department (nullable)
  - manager: Employee (nullable)
  - organization: Organization
  - costCenter: Account
  - isActive: Boolean
```

**Relationships**:
- Parent-Child hierarchy
- Managed by: Employee
- Contains: Employees, Positions
- Belongs to: Organization

**Business Rules**:
- Department code unique within organization
- Cannot delete department with employees
- Manager must be employee of department

**Domain Events**:
- DepartmentCreated
- DepartmentReorganized
- DepartmentClosed

### Attendance (Entity)

**Purpose**: Employee time and attendance record

**Properties**:
```
Attendance extends Entity:
  - employee: Employee
  - date: Date
  - checkIn: Timestamp
  - checkOut: Timestamp (nullable)
  - workHours: Duration
  - overtimeHours: Duration
  - breakTime: Duration
  - status: AttendanceStatus (Present, Absent, Late, OnLeave, Holiday)
  - location: Location (nullable)
  - notes: Text
  - approvedBy: Employee (nullable)
```

**Relationships**:
- Belongs to: Employee
- Recorded at: Location

**Business Rules**:
- One attendance record per employee per day
- Check-out must be after check-in
- Work hours calculated from check-in/out
- Late if check-in after scheduled time

**Domain Events**:
- EmployeeCheckedIn
- EmployeeCheckedOut
- AbsenceRecorded
- OvertimeRecorded

## Procurement Domain

### Supplier (Aggregate Root)

**Purpose**: Vendor providing goods or services

**Properties**:
```
Supplier extends AggregateRoot:
  - supplierNumber: String (Unique)
  - name: String
  - legalName: String
  - taxId: String
  - emailAddress: EmailAddress
  - phoneNumber: PhoneNumber
  - website: URL
  - primaryContact: ContactPerson
  - address: Address
  - paymentTerms: PaymentTerms
  - currency: CurrencyCode
  - creditLimit: Money
  - currentBalance: Money
  - rating: SupplierRating
  - status: SupplierStatus (Active, Inactive, Blocked)
  - category: SupplierCategory
  - customFields: Map<String, Any>
```

**Relationships**:
- Has many: PurchaseOrders, SupplierInvoices
- Supplies: Products (through ProductSupplier)

**Business Rules**:
- Supplier number must be unique
- Blocked suppliers cannot receive new orders
- Rating affects approval workflows

**Domain Events**:
- SupplierCreated
- SupplierRatingChanged
- SupplierBlocked

### PurchaseOrder (Aggregate Root)

**Purpose**: Order to supplier for goods/services

**Properties**:
```
PurchaseOrder extends AggregateRoot:
  - orderNumber: String (Unique)
  - supplier: Supplier
  - orderDate: Date
  - requiredDate: Date
  - expectedDate: Date
  - status: POStatus (Draft, Sent, Confirmed, Partial, Received, Cancelled)
  - receiptStatus: ReceiptStatus (Pending, Partial, Received)
  - invoiceStatus: InvoiceStatus (NotInvoiced, Partial, Invoiced)
  - subtotal: Money
  - discountAmount: Money
  - taxAmount: Money
  - shippingAmount: Money
  - totalAmount: Money
  - currency: CurrencyCode
  - exchangeRate: Decimal
  - deliveryAddress: Address
  - paymentTerms: PaymentTerms
  - lines: List<PurchaseOrderLine>
  - requisition: PurchaseRequisition (nullable)
  - notes: Text
  - terms: Text
```

**Relationships**:
- Belongs to: Supplier
- Created from: PurchaseRequisition
- Generates: GoodsReceipt, SupplierInvoice
- Has many: PurchaseOrderLines

**Business Rules**:
- Cannot modify sent orders without approval
- Cannot cancel received orders
- Total must match sum of lines
- Received quantity cannot exceed ordered

**Domain Events**:
- PurchaseOrderCreated
- PurchaseOrderSent
- PurchaseOrderConfirmed
- PurchaseOrderReceived
- PurchaseOrderCancelled

## Value Objects

### Money

**Purpose**: Represents monetary amounts with currency

**Properties**:
```
Money (Value Object):
  - amount: Decimal
  - currency: CurrencyCode
  
Methods:
  - add(Money): Money
  - subtract(Money): Money
  - multiply(Decimal): Money
  - divide(Decimal): Money
  - convertTo(CurrencyCode, ExchangeRate): Money
  - equals(Money): Boolean
```

**Rules**:
- Immutable
- Operations on same currency only (unless converting)
- Preserves decimal precision

### Address

**Purpose**: Physical or mailing address

**Properties**:
```
Address (Value Object):
  - addressLine1: String
  - addressLine2: String (nullable)
  - city: String
  - stateProvince: String
  - postalCode: String
  - country: CountryCode
  - latitude: Decimal (nullable)
  - longitude: Decimal (nullable)
  - type: AddressType (Billing, Shipping, Both)
```

### EmailAddress

**Purpose**: Validated email address

**Properties**:
```
EmailAddress (Value Object):
  - value: String
  
Validation:
  - RFC 5322 compliant
  - Format: local@domain
```

### PhoneNumber

**Purpose**: International phone number

**Properties**:
```
PhoneNumber (Value Object):
  - countryCode: String
  - number: String
  - extension: String (nullable)
  - formatted: String (computed)
```

### Quantity

**Purpose**: Amount with unit of measure

**Properties**:
```
Quantity (Value Object):
  - value: Decimal
  - unit: UnitOfMeasure
  
Methods:
  - convertTo(UnitOfMeasure): Quantity
  - add(Quantity): Quantity
  - subtract(Quantity): Quantity
```

### ContactPerson

**Purpose**: Contact information for a person

**Properties**:
```
ContactPerson (Value Object):
  - firstName: String
  - lastName: String
  - title: String
  - emailAddress: EmailAddress
  - phoneNumber: PhoneNumber
  - mobileNumber: PhoneNumber (nullable)
```

## Enumerations

### Common Status Types
- **CustomerStatus**: Active, Inactive, Blocked, Suspended
- **OrderStatus**: Draft, Confirmed, InProgress, Shipped, Delivered, Cancelled
- **PaymentStatus**: Unpaid, Partial, Paid, Overdue
- **InvoiceStatus**: Draft, Sent, Partial, Paid, Overdue, Cancelled

### Entity Types
- **ProductType**: Physical, Service, Digital, Bundle
- **AccountType**: Asset, Liability, Equity, Revenue, Expense
- **EmploymentType**: FullTime, PartTime, Contract, Intern, Temporary

## Cross-Cutting Concerns

### Audit Trail

All entities include:
- Who created (createdBy)
- When created (createdAt)
- Who last updated (updatedBy)
- When last updated (updatedAt)

### Soft Delete

All entities support soft delete:
- isDeleted flag
- deletedBy
- deletedAt
- Can be restored

### Multi-Tenancy

All entities include:
- tenantId for data isolation
- Enforced at query level
- Indexed for performance

### Versioning

Aggregate roots include:
- version field for optimistic locking
- Prevents lost updates
- Incremented on each change

### Custom Fields

Key entities support:
- customFields: Map<String, Any>
- Tenant-specific extensions
- Metadata-driven validation

## Relationships Summary

### One-to-Many Relationships
- Customer → SalesOrders
- Product → StockLevels
- Employee → Attendance
- Supplier → PurchaseOrders
- Warehouse → Locations

### Many-to-One Relationships
- SalesOrder → Customer
- StockLevel → Product
- Invoice → Customer
- PurchaseOrder → Supplier

### Many-to-Many Relationships
- Products ← → Suppliers (through ProductSupplier)
- Employees ← → Skills (through EmployeeSkill)
- Products ← → Categories (through ProductCategory)

### Self-Referencing
- Account → ParentAccount
- Department → ParentDepartment
- Employee → Manager
- Organization → ParentOrganization

## Domain Events

Events are published when significant domain actions occur:

### Naming Convention
- Past tense: "CustomerCreated", "OrderShipped"
- Domain-centric, not technical

### Event Structure
```
DomainEvent (Base):
  - eventId: UUID
  - eventType: String
  - occurredAt: Timestamp
  - tenantId: UUID
  - aggregateId: UUID
  - aggregateType: String
  - payload: JSON
  - correlationId: UUID
  - causationId: UUID
```

### Event Types by Module

**Sales**:
- CustomerCreated, OrderPlaced, OrderFulfilled, PaymentReceived

**Inventory**:
- ProductCreated, StockLevelChanged, LowStockAlert, StockTransferred

**Accounting**:
- InvoiceCreated, PaymentPosted, AccountReconciled, PeriodClosed

**HR**:
- EmployeeHired, LeaveApproved, PayrollProcessed, AttendanceMarked

**Procurement**:
- POCreated, POApproved, GoodsReceived, InvoiceMatched

## Repository Patterns

Each Aggregate Root has a corresponding repository:

```
Repository<T extends AggregateRoot>:
  - findById(UUID): Optional<T>
  - save(T): T
  - delete(T): void
  - findAll(): List<T>
  - findByTenant(UUID): List<T>
```

Specialized repositories extend base with domain-specific queries:

```
CustomerRepository extends Repository<Customer>:
  - findByCustomerNumber(String): Optional<Customer>
  - findByEmailAddress(EmailAddress): Optional<Customer>
  - findActiveCustomers(): List<Customer>
  - findCustomersWithOutstandingBalance(): List<Customer>
```

## Conclusion

This domain model provides a comprehensive foundation for the kv-saas-crm-erp system. It balances richness (capturing business complexity) with pragmatism (avoiding over-engineering). The models are designed to be:

1. **Expressive**: Reflect real business concepts
2. **Consistent**: Follow uniform patterns
3. **Flexible**: Support customization via custom fields
4. **Scalable**: Support multi-tenancy and large datasets
5. **Maintainable**: Clear boundaries and responsibilities
6. **Testable**: Pure domain logic independent of infrastructure

Use these models as a blueprint for implementation, adapting as needed for specific technology platforms and business requirements.
