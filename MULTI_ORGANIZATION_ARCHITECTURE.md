# Multi-Organization Architecture Documentation

## Overview

This document describes the comprehensive multi-organization architecture implemented in the kv-saas-crm-erp system. The architecture supports hierarchical, nested organizational structures with full tenant isolation and cross-module integration.

## Core Concepts

### 1. Organization Hierarchy

Organizations support unlimited levels of nesting:

```
Headquarters (Root)
├── Regional Office - Americas
│   ├── USA Division
│   │   ├── East Coast Branch
│   │   └── West Coast Branch
│   └── Canada Division
└── Regional Office - EMEA
    ├── UK Branch
    └── EU Branch
```

**Key Features:**
- Self-referencing parent-child relationships
- Materialized path tracking for efficient queries
- Level tracking for depth information
- Circular reference prevention
- Tenant-isolated hierarchies

### 2. Location Structure

Locations represent physical or virtual places within organizations:

```
Organization: Acme Corporation
├── Headquarters (Office)
│   ├── Admin Building
│   └── Research Lab
├── Manufacturing Plant (Factory)
│   ├── Assembly Line A
│   └── Assembly Line B
└── Warehouse Network
    ├── Central Warehouse
    │   ├── Section A
    │   └── Section B
    └── Regional Warehouse
```

**Location Types:**
- `headquarters` - Main organizational office
- `office` - Standard office location
- `branch` - Branch office
- `warehouse` - Storage facility
- `factory` - Manufacturing facility
- `retail` - Retail store
- `distribution_center` - Distribution hub
- `transit` - Transit/transfer location
- `virtual` - Virtual/remote location
- `other` - Other types

### 3. Organizational Units

Organizational units represent departments, divisions, or teams:

```
Organization: Tech Corp
└── Location: HQ Building
    ├── Engineering Division
    │   ├── Backend Team
    │   ├── Frontend Team
    │   └── DevOps Team
    ├── Sales Division
    │   ├── Enterprise Sales
    │   └── SMB Sales
    └── Operations Division
```

**Unit Types:**
- `division` - Large organizational division
- `department` - Department
- `team` - Working team
- `group` - Working group
- `project` - Project-based unit
- `other` - Other types

## Database Schema

### Organizations Table

```sql
organizations (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT FK → tenants,
    parent_id BIGINT FK → organizations (self),
    code VARCHAR(50) UNIQUE per tenant,
    name JSON (translatable),
    legal_name VARCHAR(255),
    tax_id VARCHAR(100),
    registration_number VARCHAR(100),
    organization_type ENUM,
    status ENUM,
    -- Contact & Address fields
    email, phone, fax, website,
    address_line1, address_line2, city, state, postal_code, country,
    latitude, longitude DECIMAL,
    -- Configuration
    settings JSON,
    features JSON,
    metadata JSON,
    -- Hierarchy tracking
    level INT,
    path VARCHAR (materialized path),
    -- Audit
    created_by, updated_by, created_at, updated_at, deleted_at
)
```

### Locations Table

```sql
locations (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT FK → tenants,
    organization_id BIGINT FK → organizations,
    parent_location_id BIGINT FK → locations (self),
    code VARCHAR(50) UNIQUE per tenant,
    name JSON (translatable),
    description JSON (translatable),
    location_type ENUM,
    status ENUM,
    -- Contact & Address fields
    email, phone, fax, contact_person,
    address_line1, address_line2, city, state, postal_code, country,
    latitude, longitude DECIMAL,
    -- Operating info
    operating_hours JSON,
    timezone VARCHAR(50),
    area_sqm DECIMAL,
    capacity INT,
    -- Configuration
    settings JSON,
    features JSON,
    metadata JSON,
    -- Hierarchy tracking
    level INT,
    path VARCHAR,
    -- Audit
    created_by, updated_by, created_at, updated_at, deleted_at
)
```

### Organizational Units Table

```sql
organizational_units (
    id BIGINT PRIMARY KEY,
    tenant_id BIGINT FK → tenants,
    organization_id BIGINT FK → organizations,
    location_id BIGINT FK → locations,
    parent_unit_id BIGINT FK → organizational_units (self),
    code VARCHAR(50) UNIQUE per tenant,
    name JSON (translatable),
    description JSON (translatable),
    unit_type ENUM,
    status ENUM,
    manager_id BIGINT FK → users,
    email, phone,
    -- Configuration
    settings JSON,
    metadata JSON,
    -- Hierarchy tracking
    level INT,
    path VARCHAR,
    -- Audit
    created_by, updated_by, created_at, updated_at, deleted_at
)
```

## Integration with Existing Modules

### Module Integration Pattern

All major entities now include organizational context:

```php
// Entity with organizational support
class Customer extends Model {
    use Tenantable, Organizational;
    
    protected $fillable = [
        'tenant_id',
        'organization_id',    // NEW
        'location_id',        // NEW
        // ... other fields
    ];
    
    // Automatic relationships via Organizational trait
    public function organization() { ... }
    public function location() { ... }
}
```

### Modules Updated

| Module | Entities Updated | Organizational Context |
|--------|------------------|------------------------|
| **HR** | Department, Employee, Position | Full (organization + location) |
| **Sales** | Customer, SalesOrder, Lead, Opportunity | Full (organization + location) |
| **Inventory** | Warehouse, Product, StockMovement | Full (organization + location) |
| **Accounting** | Account, JournalEntry, Invoice, Payment | Partial (organization only for accounts) |
| **Procurement** | Supplier, PurchaseOrder, Requisition | Full (organization + location) |

### Migration Strategy

For each module, we've created migrations that:

1. **Add organizational columns** to existing tables
2. **Create foreign key constraints** to maintain referential integrity
3. **Add indexes** for query performance
4. **Support rollback** for reversibility

Example migration applied to all modules:

```php
Schema::table('table_name', function (Blueprint $table) {
    // Add columns
    $table->foreignId('organization_id')
        ->nullable()
        ->after('tenant_id')
        ->constrained('organizations')
        ->nullOnDelete();
    
    $table->foreignId('location_id')
        ->nullable()
        ->after('organization_id')
        ->constrained('locations')
        ->nullOnDelete();
    
    // Add indexes
    $table->index(['tenant_id', 'organization_id']);
    $table->index(['tenant_id', 'location_id']);
});
```

## Traits and Helpers

### Hierarchical Trait

Provides tree operations for all hierarchical entities:

```php
use Modules\Organization\Traits\Hierarchical;

// Usage
$org->children;           // Direct children
$org->descendants();      // All descendants
$org->ancestors();        // All ancestors
$org->siblings();         // Siblings
$org->root();            // Root node
$org->isRoot();          // Check if root
$org->isLeaf();          // Check if leaf
$org->isDescendantOf($parent); // Check relationship
Organization::roots();    // Get all roots
Organization::buildTree($collection); // Build tree structure
```

### Organizational Trait

Provides organizational context to any entity:

```php
use Modules\Organization\Traits\Organizational;

// Usage
$entity->organization;    // Get organization
$entity->location;        // Get location
$query->forOrganization($orgId);  // Scope to organization
$query->forLocation($locId);      // Scope to location
$query->forOrganizations($ids);   // Scope to multiple orgs
```

### Materialized Path

For efficient hierarchy queries:

```
Path format: /parent_id/child_id/grandchild_id/

Examples:
- /1/          → Root node (id=1)
- /1/5/        → Child of 1 (id=5)
- /1/5/12/     → Grandchild (id=12)

Query descendants:
WHERE path LIKE '/1/%'

Query ancestors:
WHERE id IN (1, 5)  -- extracted from path
```

## API Endpoints

### Organizations

```
GET    /api/v1/organizations              # List all
POST   /api/v1/organizations              # Create
GET    /api/v1/organizations/{id}         # Get details
PUT    /api/v1/organizations/{id}         # Update
DELETE /api/v1/organizations/{id}         # Delete
GET    /api/v1/organizations/{id}/children # Get children
GET    /api/v1/organizations/{id}/hierarchy # Get full tree
GET    /api/v1/organizations/{id}/descendants # Get all descendants
```

### Locations

```
GET    /api/v1/locations                       # List all
POST   /api/v1/locations                       # Create
GET    /api/v1/locations/{id}                  # Get details
PUT    /api/v1/locations/{id}                  # Update
DELETE /api/v1/locations/{id}                  # Delete
GET    /api/v1/locations/{id}/children         # Get children
GET    /api/v1/organizations/{orgId}/locations # Get by organization
```

## Business Rules

### Organization Rules

1. **Unique Codes**: Organization codes must be unique within a tenant
2. **No Self-Reference**: Organization cannot be its own parent
3. **No Circular References**: Prevented by service layer validation
4. **Cascade Constraints**: Deleting parent sets children's parent_id to NULL
5. **Delete Protection**: Cannot delete organization with children or locations

### Location Rules

1. **Organization Required**: Every location must belong to an organization
2. **Same Organization Parent**: Parent location must belong to same organization
3. **Unique Codes**: Location codes must be unique within a tenant
4. **Delete Protection**: Cannot delete location with children

### Tenant Isolation

All organizational entities respect tenant boundaries:

```php
// Automatic tenant scoping via Tenantable trait
$organizations = Organization::all();  // Only current tenant's orgs

// Explicit multi-tenant queries require elevated permissions
$allOrgs = Organization::withoutGlobalScope('tenant')->get();
```

## Use Cases

### Example 1: Multi-Branch Retail

```
Organization: Retail Corp
├── East Region
│   ├── NYC Store (Location)
│   ├── Boston Store (Location)
│   └── Philadelphia Store (Location)
└── West Region
    ├── LA Store (Location)
    ├── SF Store (Location)
    └── Seattle Store (Location)

Sales Orders:
- Assigned to organization (region)
- Assigned to location (store)
- Inventory managed per location
- Reporting by region or store
```

### Example 2: Manufacturing Company

```
Organization: Manufacturing Inc
├── Corporate HQ (Organization)
│   └── HQ Office (Location)
├── Manufacturing Division
│   ├── Factory A (Location)
│   │   ├── Assembly (Unit)
│   │   └── Quality Control (Unit)
│   └── Factory B (Location)
└── Logistics Division
    ├── Central Warehouse (Location)
    └── Regional Warehouses (Locations)

Employees:
- Assigned to organization and location
- Department linked to organizational unit
- Reporting follows organizational hierarchy
```

### Example 3: Professional Services

```
Organization: Consulting Firm
├── North America
│   ├── Technology Practice
│   │   ├── Cloud Team (Unit)
│   │   └── Security Team (Unit)
│   └── Business Practice
│       ├── Strategy Team (Unit)
│       └── Operations Team (Unit)
└── EMEA
    └── Technology Practice
        └── Digital Transformation (Unit)

Projects & Resources:
- Assigned to practice (organization)
- Staff assigned to teams (units)
- Time tracking by organization
- Billing by practice
```

## Performance Considerations

### Indexing Strategy

All organizational tables include:

```sql
-- Single column indexes
INDEX (tenant_id)
INDEX (parent_id)
INDEX (status)
INDEX (level)
INDEX (path)

-- Composite indexes
INDEX (tenant_id, organization_id)
INDEX (tenant_id, parent_id)
INDEX (organization_id, status)
INDEX (tenant_id, location_id)
```

### Query Optimization

1. **Use Materialized Path**: For deep hierarchy queries
2. **Eager Load Relationships**: Prevent N+1 queries
3. **Cache Hierarchy Trees**: Cache frequently accessed trees
4. **Limit Depth**: Consider max depth limits for UI
5. **Pagination**: Always paginate large result sets

### Caching Recommendations

```php
// Cache organization tree
$tree = Cache::remember(
    "org_tree_{$tenantId}",
    3600,
    fn() => Organization::buildTree(Organization::all())
);

// Cache user's accessible organizations
$userOrgs = Cache::remember(
    "user_orgs_{$userId}",
    1800,
    fn() => $user->accessibleOrganizations()
);
```

## Security Considerations

### Access Control

1. **Tenant Isolation**: Automatic via Tenantable trait
2. **Organization-Based Permissions**: Via IAM module policies
3. **Location-Based Access**: Restrict by assigned locations
4. **Hierarchy-Based Inheritance**: Permissions can inherit down tree

### Data Isolation

```php
// Always scoped to tenant
Organization::all(); // Only current tenant

// Organization-level scoping
$customers = Customer::forOrganization($orgId)->get();

// Location-level scoping
$orders = SalesOrder::forLocation($locationId)->get();
```

## Migration Guide

### For Existing Deployments

1. **Backup Database**: Always backup before migration
2. **Run Migrations**: Apply organizational column migrations
3. **Set Default Organization**: Create default organization per tenant
4. **Backfill Data**: Set organization_id for existing records
5. **Update Code**: Add Organizational trait to entities
6. **Test Thoroughly**: Verify tenant isolation still works

```bash
# Migration sequence
php artisan migrate --path=Modules/Organization/Database/Migrations
php artisan migrate --path=Modules/HR/Database/Migrations
php artisan migrate --path=Modules/Sales/Database/Migrations
php artisan migrate --path=Modules/Inventory/Database/Migrations
php artisan migrate --path=Modules/Accounting/Database/Migrations
php artisan migrate --path=Modules/Procurement/Database/Migrations
```

### Backfilling Strategy

```php
// Create default organization per tenant
$tenants = Tenant::all();
foreach ($tenants as $tenant) {
    $org = Organization::create([
        'tenant_id' => $tenant->id,
        'code' => 'HQ',
        'name' => ['en' => $tenant->name],
        'organization_type' => 'headquarters',
        'status' => 'active',
    ]);
    
    // Update existing records
    Customer::where('tenant_id', $tenant->id)
        ->whereNull('organization_id')
        ->update(['organization_id' => $org->id]);
}
```

## Future Enhancements

1. **Organization Templates**: Pre-defined organizational structures
2. **Organization Transfer**: Move entities between organizations
3. **Cross-Organization Reporting**: Consolidated reports
4. **Organization Branding**: Per-organization UI customization
5. **Organizational Workflows**: Organization-specific approval flows
6. **Location Services**: Geocoding, distance calculations, routing
7. **Multi-Organization Inventory**: Cross-location stock visibility
8. **Transfer Orders**: Inter-location inventory transfers

## References

- Organization Module: `/Modules/Organization/README.md`
- Hierarchical Trait: `/Modules/Organization/Traits/Hierarchical.php`
- Organizational Trait: `/Modules/Organization/Traits/Organizational.php`
- Migration Template: `/Modules/Organization/Database/Migrations/migration_template_add_organizational_columns.php`

---

**Version**: 1.0.0  
**Last Updated**: 2024-02-10  
**Maintained By**: kv-saas-crm-erp Development Team
