# Tenancy Module API - Quick Reference Card

## 📍 Base URL
```
/api/v1/tenants
```

## 🔑 Authentication
All endpoints require `auth:sanctum` middleware.

## 📋 API Endpoints

### RESTful CRUD

```http
GET    /api/v1/tenants              # List tenants (paginated)
POST   /api/v1/tenants              # Create tenant
GET    /api/v1/tenants/{id}         # Show tenant
PUT    /api/v1/tenants/{id}         # Update tenant (full)
PATCH  /api/v1/tenants/{id}         # Update tenant (partial)
DELETE /api/v1/tenants/{id}         # Delete tenant
```

### Custom Endpoints

```http
GET    /api/v1/tenants/search?q={query}    # Search tenants
GET    /api/v1/tenants/active               # Get active tenants
POST   /api/v1/tenants/{id}/activate        # Activate tenant
POST   /api/v1/tenants/{id}/deactivate      # Deactivate tenant
POST   /api/v1/tenants/{id}/suspend         # Suspend tenant
```

## 📝 Request Examples

### Create Tenant
```bash
curl -X POST http://localhost:8000/api/v1/tenants \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Corp",
    "slug": "acme-corp",
    "domain": "acme.example.com",
    "status": "active",
    "settings": {},
    "features": ["crm", "inventory"],
    "limits": {"users": 100, "storage": "50GB"}
  }'
```

### Update Tenant
```bash
curl -X PUT http://localhost:8000/api/v1/tenants/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Corporation",
    "status": "active"
  }'
```

### Search Tenants
```bash
curl -X GET "http://localhost:8000/api/v1/tenants/search?q=acme" \
  -H "Authorization: Bearer {token}"
```

### Activate Tenant
```bash
curl -X POST http://localhost:8000/api/v1/tenants/1/activate \
  -H "Authorization: Bearer {token}"
```

## 📊 Response Format

### Success Response (Single)
```json
{
  "data": {
    "id": 1,
    "name": "Acme Corp",
    "slug": "acme-corp",
    "domain": "acme.example.com",
    "status": "active",
    "settings": {},
    "features": ["crm", "inventory"],
    "limits": {"users": 100, "storage": "50GB"},
    "is_active": true,
    "on_trial": false,
    "has_active_subscription": true,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

### Success Response (Collection)
```json
{
  "data": [
    {
      "id": 1,
      "name": "Acme Corp",
      "slug": "acme-corp",
      ...
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/tenants?page=1",
    "last": "http://localhost/api/v1/tenants?page=10",
    "prev": null,
    "next": "http://localhost/api/v1/tenants?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### Error Response
```json
{
  "message": "Validation failed",
  "errors": {
    "slug": ["The slug has already been taken."],
    "name": ["The name field is required."]
  }
}
```

## ✅ Validation Rules

### Create Tenant (StoreTenantRequest)
| Field | Rules |
|-------|-------|
| name | required, string, max:255 |
| slug | required, unique, alpha_dash, max:255 |
| domain | nullable, unique, max:255 |
| database | nullable, string, max:255 |
| schema | nullable, string, max:255 |
| status | nullable, in:active,inactive,suspended |
| settings | nullable, array |
| features | nullable, array |
| limits | nullable, array |
| trial_ends_at | nullable, date, after:now |
| subscription_ends_at | nullable, date, after:now |

### Update Tenant (UpdateTenantRequest)
Same rules with `sometimes` prefix and `Rule::unique()->ignore()` for slug/domain.

## 🔐 Required Permissions

| Permission | Description |
|------------|-------------|
| tenant.view | View tenants |
| tenant.create | Create tenants |
| tenant.update | Update tenants |
| tenant.delete | Delete tenants |
| tenant.activate | Activate tenants |
| tenant.deactivate | Deactivate tenants |
| tenant.suspend | Suspend tenants |
| tenant.view-stats | View tenant statistics |

## 🎭 Authorized Roles

- **super-admin**: Full access to all operations
- **admin**: Management operations
- **tenant-manager**: Tenant-specific management
- **analyst**: View and statistics access

## 📦 Module Structure

```
Modules/Tenancy/
├── Repositories/
│   ├── Contracts/TenantRepositoryInterface.php
│   └── TenantRepository.php
├── Services/
│   └── TenantService.php
├── Http/
│   ├── Controllers/Api/TenantController.php
│   ├── Requests/
│   │   ├── StoreTenantRequest.php
│   │   └── UpdateTenantRequest.php
│   └── Resources/TenantResource.php
├── Policies/
│   └── TenantPolicy.php
└── Events/
    ├── TenantCreated.php
    ├── TenantUpdated.php
    └── TenantDeleted.php
```

## 🔄 Events Dispatched

| Event | When | Payload |
|-------|------|---------|
| TenantCreated | After tenant creation | Tenant model |
| TenantUpdated | After tenant update | Tenant model |
| TenantDeleted | After tenant deletion | Tenant model |

## 🔧 Service Methods

```php
// TenantService methods
$tenantService->create(array $data): Tenant
$tenantService->update(int|string $id, array $data): Tenant
$tenantService->delete(int|string $id): bool
$tenantService->activate(int|string $id): Tenant
$tenantService->deactivate(int|string $id): Tenant
$tenantService->suspend(int|string $id): Tenant
$tenantService->findById(int|string $id): ?Tenant
$tenantService->findBySlug(string $slug): ?Tenant
$tenantService->findByDomain(string $domain): ?Tenant
$tenantService->getActiveTenants(): Collection
$tenantService->search(string $query): Collection
$tenantService->getPaginated(int $perPage = 15): LengthAwarePaginator
```

## 📊 HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success (GET, PUT, PATCH) |
| 201 | Created (POST) |
| 404 | Not Found |
| 422 | Validation Failed |
| 500 | Server Error |

## 🧪 Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Tenancy

# Run with coverage
php artisan test --coverage

# Test specific file
php artisan test Modules/Tenancy/Tests/Feature/TenantApiTest.php
```

## 📝 Notes

- All endpoints use transactions for data integrity
- Comprehensive logging in service layer
- Authorization checks on all endpoints
- Events dispatched for cross-module communication
- Repository pattern for testability
- Service layer for business logic isolation

## 🔗 Related Documentation

- **Full Implementation**: `TENANCY_API_IMPLEMENTATION.md`
- **Architecture**: `ARCHITECTURE.md`
- **Domain Models**: `DOMAIN_MODELS.md`
- **Module Development**: `MODULE_DEVELOPMENT_GUIDE.md`
