# Organization Module

## Overview

The Organization module provides comprehensive multi-level organizational structure management for the kv-saas-crm-erp system. It supports hierarchical organizations, branches, locations, and organizational units with full tenant isolation.

## Features

- **Hierarchical Organizations**: Unlimited nesting of parent-child organization relationships
- **Multi-Location Support**: Support for branches, offices, warehouses, and other location types
- **Organizational Units**: Flexible organizational structure for departments and divisions
- **Tenant Isolation**: Full multi-tenant support with automatic scoping
- **Address Management**: Comprehensive address handling with geocoding support
- **Settings & Configuration**: Organization-level settings and configuration management
- **Status Management**: Lifecycle management (active, inactive, suspended)

## Entities

### Organization
- Represents a company, subsidiary, or organizational entity
- Supports parent-child hierarchies
- Configurable settings and features
- Contact information and addresses

### Location
- Represents physical or virtual locations (offices, branches, warehouses)
- Belongs to an organization
- Supports location hierarchy
- Geocoding support (latitude/longitude)
- Operating hours and contact information

### OrganizationalUnit
- Represents departments, divisions, or teams
- Links to organizations and locations
- Manager assignment
- Hierarchical structure

## Architecture

### Database Schema

```
organizations
├── id (PK)
├── tenant_id (FK)
├── parent_id (FK, self-reference)
├── code (unique per tenant)
├── name (translatable)
├── legal_name
├── tax_id
├── status
├── settings (JSON)
└── timestamps

locations
├── id (PK)
├── tenant_id (FK)
├── organization_id (FK)
├── parent_location_id (FK, self-reference)
├── code (unique per tenant)
├── name (translatable)
├── location_type
├── address fields
├── latitude, longitude
└── timestamps

organizational_units
├── id (PK)
├── tenant_id (FK)
├── organization_id (FK)
├── location_id (FK)
├── parent_unit_id (FK, self-reference)
├── code (unique per tenant)
├── name (translatable)
├── manager_id (FK to users)
└── timestamps
```

## Usage Examples

### Creating Organizations

```php
use Modules\Organization\Services\OrganizationService;

$organizationService = app(OrganizationService::class);

// Create parent organization
$parentOrg = $organizationService->createOrganization([
    'code' => 'HQ',
    'name' => 'Headquarters',
    'legal_name' => 'Company Inc.',
    'tax_id' => 'TAX123456',
    'status' => 'active',
]);

// Create subsidiary
$subsidiary = $organizationService->createOrganization([
    'parent_id' => $parentOrg->id,
    'code' => 'SUB-01',
    'name' => 'Regional Subsidiary',
    'legal_name' => 'Subsidiary LLC',
    'status' => 'active',
]);
```

### Creating Locations

```php
use Modules\Organization\Services\LocationService;

$locationService = app(LocationService::class);

$location = $locationService->createLocation([
    'organization_id' => $parentOrg->id,
    'code' => 'NYC-01',
    'name' => 'New York Office',
    'location_type' => 'office',
    'address_line1' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'postal_code' => '10001',
    'country' => 'US',
]);
```

### Querying Hierarchies

```php
// Get all child organizations
$children = $organization->children;

// Get all descendants (recursive)
$descendants = $organization->descendants();

// Get ancestors (up the tree)
$ancestors = $organization->ancestors();

// Get root organization
$root = $organization->root();

// Check if organization is descendant of another
$isChild = $organization->isDescendantOf($parentOrg);
```

## Integration with Other Modules

The Organization module is designed to integrate with all other modules:

- **HR Module**: Departments and employees belong to organizations/locations
- **Sales Module**: Customers and orders can be associated with organizations
- **Inventory Module**: Warehouses are linked to locations
- **Accounting Module**: Financial records are scoped to organizations
- **Procurement Module**: Suppliers and purchases are organization-specific

## API Endpoints

### Organizations
- `GET /api/v1/organizations` - List all organizations
- `POST /api/v1/organizations` - Create organization
- `GET /api/v1/organizations/{id}` - Get organization details
- `PUT /api/v1/organizations/{id}` - Update organization
- `DELETE /api/v1/organizations/{id}` - Delete organization
- `GET /api/v1/organizations/{id}/children` - Get child organizations
- `GET /api/v1/organizations/{id}/hierarchy` - Get full hierarchy

### Locations
- `GET /api/v1/locations` - List all locations
- `POST /api/v1/locations` - Create location
- `GET /api/v1/locations/{id}` - Get location details
- `PUT /api/v1/locations/{id}` - Update location
- `DELETE /api/v1/locations/{id}` - Delete location
- `GET /api/v1/locations/{id}/children` - Get child locations

## Testing

Run module tests:
```bash
php artisan test --testsuite=Organization
```

## Dependencies

- Core Module
- Tenancy Module

## License

Proprietary - Part of kv-saas-crm-erp system
