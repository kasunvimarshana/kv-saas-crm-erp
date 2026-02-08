# Database Factories and Seeders Documentation

This documentation covers the comprehensive model factories and database seeders for the Sales and Tenancy modules.

## Overview

The factories and seeders provide realistic demo data for development, testing, and demonstration purposes. All factories follow Laravel 11 conventions with strict type declarations and proper PHPDoc comments.

## Factories

### Location
- Tenancy: `Modules/Tenancy/Database/Factories/`
- Sales: `Modules/Sales/Database/Factories/`

### Available Factories

#### 1. TenantFactory

**Location:** `Modules/Tenancy/Database/Factories/TenantFactory.php`

**Purpose:** Generates realistic tenant data with domains, settings, and subscription information.

**Default State:**
- Company name and slug
- Unique domain
- Active status
- Comprehensive settings (timezone, locale, currency, etc.)
- Feature flags (multi-currency, API access, etc.)
- Resource limits (users, storage, API calls)
- Active subscription

**State Methods:**
```php
Tenant::factory()->onTrial()->create();           // Create tenant on trial
Tenant::factory()->suspended()->create();          // Create suspended tenant
Tenant::factory()->inactive()->create();           // Create inactive tenant
Tenant::factory()->expired()->create();            // Create tenant with expired subscription
Tenant::factory()->smallBusiness()->create();      // Create small business tenant with limits
Tenant::factory()->enterprise()->create();         // Create enterprise tenant (no limits)
```

**Example Usage:**
```php
// Create a basic tenant
$tenant = Tenant::factory()->create();

// Create an enterprise tenant on trial
$tenant = Tenant::factory()->enterprise()->onTrial()->create();

// Create multiple small business tenants
$tenants = Tenant::factory()->smallBusiness()->count(5)->create();
```

---

#### 2. CustomerFactory

**Location:** `Modules/Sales/Database/Factories/CustomerFactory.php`

**Purpose:** Generates realistic customer data with company names, contact information, and credit limits.

**Default State:**
- Unique customer number
- Mix of company (70%) and individual (30%) customers
- Contact information (email, phone, mobile)
- Payment terms and credit limits
- Active status
- Realistic tags

**State Methods:**
```php
Customer::factory()->inactive()->create();         // Create inactive customer
Customer::factory()->suspended()->create();        // Create suspended customer
Customer::factory()->vip()->create();             // Create VIP customer (high credit limit)
Customer::factory()->company()->create();          // Create company customer
Customer::factory()->individual()->create();       // Create individual customer
Customer::factory()->wholesale()->create();        // Create wholesale customer
Customer::factory()->noCreditLimit()->create();    // Create customer with no credit limit
```

**Example Usage:**
```php
// Create customers for a specific tenant
$customer = Customer::factory()->create(['tenant_id' => 1]);

// Create VIP wholesale customer
$customer = Customer::factory()->vip()->wholesale()->create(['tenant_id' => 1]);

// Create multiple individual customers
$customers = Customer::factory()->individual()->count(10)->create(['tenant_id' => 1]);
```

---

#### 3. LeadFactory

**Location:** `Modules/Sales/Database/Factories/LeadFactory.php`

**Purpose:** Generates lead data with various statuses, sources, and probabilities.

**Default State:**
- Unique lead number
- Random status (new, contacted, qualified, negotiation, won, lost)
- Appropriate stage and probability based on status
- Contact information
- Expected revenue and close date
- Source tracking
- Tags

**State Methods:**
```php
Lead::factory()->newLead()->create();             // Create new lead (10-20% probability)
Lead::factory()->qualified()->create();           // Create qualified lead (40-60% probability)
Lead::factory()->won()->create();                // Create won lead (100% probability)
Lead::factory()->lost()->create();               // Create lost lead (0% probability)
Lead::factory()->negotiation()->create();        // Create lead in negotiation (75-90% probability)
Lead::factory()->hot()->create();                // Create hot lead (70-90% probability)
Lead::factory()->fromWebsite()->create();        // Create lead from website
Lead::factory()->fromReferral()->create();       // Create lead from referral (higher probability)
Lead::factory()->converted()->create();          // Create converted lead (requires customer_id)
```

**Example Usage:**
```php
// Create a new lead
$lead = Lead::factory()->create(['tenant_id' => 1]);

// Create a hot lead in negotiation
$lead = Lead::factory()->hot()->negotiation()->create(['tenant_id' => 1]);

// Create multiple qualified leads
$leads = Lead::factory()->qualified()->count(5)->create(['tenant_id' => 1]);

// Create converted lead
$lead = Lead::factory()->converted()->create([
    'tenant_id' => 1,
    'customer_id' => $customer->id,
]);
```

---

#### 4. SalesOrderFactory

**Location:** `Modules/Sales/Database/Factories/SalesOrderFactory.php`

**Purpose:** Generates sales orders with realistic totals, dates, and statuses.

**Default State:**
- Unique order number
- Order and delivery dates
- Random status and payment status
- Calculated totals (subtotal, tax, discount, shipping)
- Payment method
- Currency
- Notes and terms

**State Methods:**
```php
SalesOrder::factory()->draft()->create();          // Create draft order
SalesOrder::factory()->pending()->create();        // Create pending order
SalesOrder::factory()->confirmed()->create();      // Create confirmed order
SalesOrder::factory()->shipped()->create();        // Create shipped order
SalesOrder::factory()->delivered()->create();      // Create delivered order (paid)
SalesOrder::factory()->cancelled()->create();      // Create cancelled order
SalesOrder::factory()->paid()->create();          // Create paid order
SalesOrder::factory()->noDiscount()->create();     // Create order without discount
SalesOrder::factory()->noShipping()->create();     // Create order without shipping cost
SalesOrder::factory()->large()->create();         // Create high-value order (50k-500k)
```

**Example Usage:**
```php
// Create order for a customer
$order = SalesOrder::factory()->create([
    'tenant_id' => 1,
    'customer_id' => $customer->id,
]);

// Create confirmed paid order
$order = SalesOrder::factory()->confirmed()->paid()->create([
    'tenant_id' => 1,
    'customer_id' => $customer->id,
]);

// Create large enterprise order
$order = SalesOrder::factory()->large()->delivered()->create([
    'tenant_id' => 1,
    'customer_id' => $customer->id,
]);
```

---

#### 5. SalesOrderLineFactory

**Location:** `Modules/Sales/Database/Factories/SalesOrderLineFactory.php`

**Purpose:** Generates order line items with quantities, prices, discounts, and taxes.

**Default State:**
- Product description
- Quantity and unit price
- Optional discount (30% chance)
- Tax calculation
- Automatic total calculation

**State Methods:**
```php
SalesOrderLine::factory()->noDiscount()->create();  // Create line without discount
SalesOrderLine::factory()->noTax()->create();      // Create line without tax
SalesOrderLine::factory()->service()->create();    // Create service line item
SalesOrderLine::factory()->bulk()->create();       // Create high-quantity line (bulk order)
SalesOrderLine::factory()->premium()->create();    // Create high-value line item
```

**Example Usage:**
```php
// Create order line
$line = SalesOrderLine::factory()->create([
    'tenant_id' => 1,
    'sales_order_id' => $order->id,
]);

// Create multiple lines for an order
SalesOrderLine::factory()->count(5)->create([
    'tenant_id' => 1,
    'sales_order_id' => $order->id,
]);

// Create premium service line
$line = SalesOrderLine::factory()->service()->premium()->create([
    'tenant_id' => 1,
    'sales_order_id' => $order->id,
]);
```

---

## Seeders

### Location
All seeders are in `database/seeders/`

### Available Seeders

#### 1. TenantSeeder

**Purpose:** Seeds 5 demo tenants with various configurations.

**Creates:**
- 1 main demo tenant (Acme Corporation) - fully configured
- 1 trial tenant (TechStart Solutions)
- 1 small business tenant (Local Retail Shop)
- 1 enterprise tenant (Global Enterprises Inc)
- 1 random tenant

**Run:**
```bash
php artisan db:seed --class=TenantSeeder
```

---

#### 2. CustomerSeeder

**Purpose:** Seeds 35 customers with various types and statuses.

**Creates:**
- 3 VIP customers
- 5 wholesale customers
- 10 company customers
- 8 individual customers
- 2 inactive customers
- 1 suspended customer
- 6 random customers

**Run:**
```bash
php artisan db:seed --class=CustomerSeeder
```

**Note:** Requires tenants to exist (run TenantSeeder first).

---

#### 3. LeadSeeder

**Purpose:** Seeds 20-24 leads in various stages of the sales pipeline.

**Creates:**
- 3 new leads
- 4 qualified leads
- 3 hot leads in negotiation
- 4 won leads
- 3 lost leads
- 3 converted leads (if customers exist)
- 2 website leads
- 2 referral leads

**Run:**
```bash
php artisan db:seed --class=LeadSeeder
```

**Note:** Requires tenants and optionally customers for converted leads.

---

#### 4. SalesOrderSeeder

**Purpose:** Seeds 16+ sales orders with order lines in various statuses.

**Creates:**
- 2 draft orders (2-6 lines each)
- 3 pending orders (2-6 lines each)
- 4 confirmed orders (2-6 lines each)
- 3 shipped orders (2-6 lines each)
- 3 delivered orders (2-6 lines each)
- 1 large order (8 lines)
- 1 cancelled order (2-6 lines)

**Run:**
```bash
php artisan db:seed --class=SalesOrderSeeder
```

**Note:** Requires tenants and customers. Automatically calculates order totals based on lines.

---

#### 5. DemoDataSeeder (Main Seeder)

**Purpose:** Orchestrates all seeders in the correct order with foreign key dependencies.

**Execution Order:**
1. TenantSeeder
2. CustomerSeeder
3. LeadSeeder
4. SalesOrderSeeder

**Run:**
```bash
php artisan db:seed --class=DemoDataSeeder
```

**Output:** Provides detailed progress and summary table showing what was seeded.

---

## Usage Examples

### Development Environment Setup

```bash
# Seed all demo data
php artisan db:seed --class=DemoDataSeeder

# Or seed individually
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=LeadSeeder
php artisan db:seed --class=SalesOrderSeeder
```

### Testing

```php
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Entities\SalesOrderLine;

/** @test */
public function it_calculates_order_totals_correctly()
{
    // Arrange
    $customer = Customer::factory()->create(['tenant_id' => 1]);
    $order = SalesOrder::factory()->create([
        'tenant_id' => 1,
        'customer_id' => $customer->id,
    ]);
    
    SalesOrderLine::factory()->count(3)->create([
        'tenant_id' => 1,
        'sales_order_id' => $order->id,
    ]);
    
    // Act
    $order->calculateTotals();
    
    // Assert
    $this->assertGreaterThan(0, $order->fresh()->total_amount);
}
```

### Creating Test Data with Specific Attributes

```php
// Create a complete sales scenario
$tenant = Tenant::factory()->create();

$customer = Customer::factory()->vip()->create([
    'tenant_id' => $tenant->id,
]);

$order = SalesOrder::factory()->confirmed()->paid()->create([
    'tenant_id' => $tenant->id,
    'customer_id' => $customer->id,
]);

SalesOrderLine::factory()->count(5)->create([
    'tenant_id' => $tenant->id,
    'sales_order_id' => $order->id,
]);

$order->calculateTotals();
```

---

## Features

### ✅ Realistic Data
- Uses Faker to generate realistic company names, emails, phone numbers
- Proper currency formatting and amounts
- Realistic date ranges and probabilities
- Context-aware data (e.g., won leads have 100% probability)

### ✅ State Methods
- Chainable state methods for flexible data generation
- Example: `Customer::factory()->vip()->wholesale()->create()`

### ✅ Relationships
- Factories respect foreign key relationships
- Automatic total calculations for orders
- Proper tenant isolation

### ✅ Extensibility
- Easy to add new states
- Customizable attributes
- Can override any default values

### ✅ Laravel 11 Compliance
- Uses `declare(strict_types=1)`
- Type hints for all parameters and returns
- PHPDoc comments
- Follows PSR-12 and Laravel coding standards

---

## Notes

1. **Tenant Context:** Most entities require a `tenant_id`. Default is `1` in factories, but should be explicitly set in seeders.

2. **Order Totals:** When creating `SalesOrder` with lines, always call `$order->calculateTotals()` after adding lines to ensure accurate totals.

3. **Factory Discovery:** Laravel automatically discovers factories using the `newFactory()` method in models.

4. **Seeder Order:** Always seed in this order to respect foreign keys:
   - Tenants → Customers → Leads/Orders → Order Lines

5. **Data Reset:** To reset and re-seed:
   ```bash
   php artisan migrate:fresh --seed --seeder=DemoDataSeeder
   ```

---

## Troubleshooting

### Factory Not Found
If you get "Unable to locate factory", ensure:
1. The model has `use HasFactory` trait
2. The model has `newFactory()` method
3. Factory namespace matches module structure

### Foreign Key Constraint Errors
Run seeders in correct order or use `DemoDataSeeder` which handles dependencies.

### Class Not Found
Run composer dump-autoload:
```bash
composer dump-autoload
```

---

## Contributing

When adding new factories or seeders:
1. Follow existing naming conventions
2. Add PHPDoc comments
3. Include state methods for common scenarios
4. Update this documentation
5. Run `./vendor/bin/pint` before committing
6. Test with `php artisan db:seed`

---

**Last Updated:** February 2024
**Laravel Version:** 11.x
**PHP Version:** 8.2+
