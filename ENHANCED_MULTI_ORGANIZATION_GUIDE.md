# Enhanced Multi-Organization Architecture Implementation

## Overview

This document describes the enhanced multi-organization architecture implementation that provides advanced hierarchical organization management, cross-organization data access control, and user-based organization context switching.

## Core Components

### 1. OrganizationContext Middleware

**Location**: `Modules/Core/Http/Middleware/OrganizationContext.php`

**Purpose**: Automatically resolves and validates organization context for API requests.

**Resolution Order**:
1. `X-Organization-ID` header (explicit organization selection)
2. `organization_id` query parameter
3. Authenticated user's default organization
4. Tenant's default/root organization

**Features**:
- Validates organization belongs to current tenant
- Checks organization status (must be active)
- Stores organization context in session
- Optionally resolves and sets location context
- Returns 403 if organization invalid or inactive

**Usage**:
```php
// Apply to routes requiring organization context
Route::middleware(['tenant.context', 'organization.context'])->group(function () {
    Route::apiResource('customers', CustomerController::class);
});
```

### 2. OrganizationHierarchyService

**Location**: `Modules/Organization/Services/OrganizationHierarchyService.php`

**Purpose**: Provides advanced hierarchy operations with caching and access control.

**Key Methods**:

#### Hierarchy Traversal
- `getAncestors($organizationId)` - Get all parent organizations (excluding self)
- `getAncestorsIncludingSelf($organizationId)` - Get all parent organizations (including self)
- `getDescendants($organizationId)` - Get all child organizations (excluding self)
- `getDescendantsIncludingSelf($organizationId)` - Get all child organizations (including self)
- `getChildren($organizationId)` - Get immediate children only
- `getSiblings($organizationId, $includeSelf)` - Get organizations with same parent
- `getFullTree($organizationId)` - Get ancestors, self, and descendants

#### Access Control
- `hasAccess($userId, $organizationId, $visibility)` - Check if user can access organization
- `getAccessibleOrganizations($userId, $visibility)` - Get all accessible organizations for user
- `isInSubtree($targetOrgId, $referenceOrgId)` - Check if target is in reference's subtree
- `isInSameTree($org1Id, $org2Id)` - Check if organizations share root
- `isInSameTenant($org1Id, $org2Id)` - Check if organizations in same tenant

#### Organization Management
- `moveOrganization($organizationId, $newParentId)` - Move organization to new parent
- `bulkMoveOrganizations($organizationIds, $newParentId)` - Move multiple organizations
- `clearHierarchyCache($organizationId)` - Clear cached hierarchy data

#### Utility Methods
- `getRootOrganizations($tenantId)` - Get all root organizations for tenant
- `getPathFromRoot($organizationId)` - Get path from root to organization
- `getBreadcrumb($organizationId)` - Get breadcrumb array
- `getDepth($organizationId)` - Get organization level in hierarchy

**Visibility Levels**:
- `own` - Only user's organization
- `children` - User's organization and all descendants
- `tree` - Entire tree (ancestors, self, descendants)
- `tenant` - All organizations in tenant

**Caching**:
All hierarchy queries are cached for 1 hour to improve performance. Cache is automatically cleared when organizations are moved or modified.

### 3. OrganizationPolicy

**Location**: `Modules/Organization/Policies/OrganizationPolicy.php`

**Purpose**: Defines authorization rules for organization access with hierarchical support.

**Key Methods**:
- `viewAny($user)` - Can view any organizations
- `view($user, $organization)` - Can view specific organization
- `create($user)` - Can create organizations
- `update($user, $organization)` - Can update organization
- `delete($user, $organization)` - Can delete organization
- `move($user, $organization)` - Can move organization to different parent
- `manageSettings($user, $organization)` - Can manage organization settings
- `manageFeatures($user, $organization)` - Can manage organization features
- `viewHierarchy($user, $organization)` - Can view organization hierarchy
- `switchTo($user, $organization)` - Can switch to this organization

**Authorization Rules**:
- All operations check tenant isolation first
- View operations respect user's visibility settings
- Update/delete operations limited to user's subtree
- Root organizations cannot be deleted
- Organizations with children cannot be deleted
- Super admins have unrestricted access

### 4. HierarchicalOrganizational Trait

**Location**: `Modules/Organization/Traits/HierarchicalOrganizational.php`

**Purpose**: Provides advanced query scopes for entities with organizational context.

**Query Scopes**:

```php
// Basic scopes
Customer::forOrganization($organizationId)->get();
Customer::forLocation($locationId)->get();
Customer::forOrganizations([$org1, $org2])->get();

// Hierarchical scopes
Customer::forOrganizationTree($organizationId)->get(); // Org + all descendants
Customer::forOrganizationAndChildren($organizationId)->get(); // Org + direct children
Customer::forOrganizationAncestors($organizationId, $includeSelf)->get(); // Parent orgs

// User-based scopes
Customer::forCurrentUserOrganizations('children')->get(); // Based on user's visibility

// Level-based scopes
Customer::forOrganizationLevel(2)->get(); // All at level 2
Customer::forRootOrganizations()->get(); // Root orgs only
Customer::forLeafOrganizations()->get(); // Leaf orgs only

// Sibling scopes
$customer->scopeSiblingOrganizations(); // Entities in sibling organizations
```

**Helper Methods**:
```php
$customer->belongsToOrganizationTree($organizationId); // Check if in subtree
$customer->belongsToSameTree($organizationId); // Check if in same tree
```

### 5. UserOrganization Trait

**Location**: `Modules/Organization/Traits/UserOrganization.php`

**Purpose**: Provides organization-related methods for User model.

**Key Methods**:

```php
// Organization relationships
$user->organization; // BelongsTo relationship
$user->location; // BelongsTo relationship
$user->getDefaultOrganizationId();
$user->getDefaultLocationId();

// Role checking
$user->isSuperAdmin(); // Can access all organizations
$user->isOrganizationAdmin($organizationId); // Admin for specific org

// Settings management
$user->getSetting('key', 'default');
$user->setSetting('key', 'value');

// Organization switching
$user->switchOrganization($organizationId); // Switch active org
$user->switchLocation($locationId); // Switch active location

// Access checking
$user->getAccessibleOrganizations('children'); // Get accessible orgs
$user->canAccessOrganization($organizationId, 'tree'); // Check access
$user->getOrganizationBreadcrumb(); // Get hierarchy path
$user->getOrganizationVisibility(); // Get visibility setting
$user->setOrganizationVisibility('children'); // Set visibility
```

### 6. OrganizationHierarchyController

**Location**: `Modules/Organization/Http/Controllers/Api/OrganizationHierarchyController.php`

**Purpose**: Provides API endpoints for hierarchical organization operations.

**Endpoints**:

```
GET    /api/organizations/{id}/ancestors     - Get parent organizations
GET    /api/organizations/{id}/descendants   - Get child organizations
GET    /api/organizations/{id}/children      - Get immediate children
GET    /api/organizations/{id}/siblings      - Get sibling organizations
GET    /api/organizations/{id}/full-tree     - Get entire tree
GET    /api/organizations/{id}/breadcrumb    - Get path from root
GET    /api/organizations/roots              - Get root organizations
POST   /api/organizations/{id}/move          - Move organization
GET    /api/organizations/accessible         - Get accessible organizations
GET    /api/organizations/{id}/check-access  - Check access permission
```

**Example Requests**:

```bash
# Get all descendants
curl -X GET http://api.example.com/api/organizations/5/descendants \
  -H "Authorization: Bearer {token}" \
  -H "X-Tenant-ID: 1"

# Move organization to new parent
curl -X POST http://api.example.com/api/organizations/10/move \
  -H "Authorization: Bearer {token}" \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -d '{"parent_id": 8}'

# Get accessible organizations for user (children visibility)
curl -X GET http://api.example.com/api/organizations/accessible?visibility=children \
  -H "Authorization: Bearer {token}" \
  -H "X-Tenant-ID: 1"

# Check if user can access organization
curl -X GET http://api.example.com/api/organizations/15/check-access?visibility=tree \
  -H "Authorization: Bearer {token}" \
  -H "X-Tenant-ID: 1"
```

## Usage Patterns

### Pattern 1: Apply Organization Context to Routes

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'tenant.context', 'organization.context'])
    ->prefix('v1')
    ->group(function () {
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('orders', OrderController::class);
    });
```

### Pattern 2: Query Data with Hierarchical Filtering

```php
// In a controller or service
class CustomerService
{
    public function getCustomersForUserOrganization(User $user)
    {
        // Get customers based on user's organization visibility
        $visibility = $user->getOrganizationVisibility(); // e.g., 'children'
        
        return Customer::forCurrentUserOrganizations($visibility)
            ->where('status', 'active')
            ->get();
    }
    
    public function getCustomersInOrganizationTree(int $organizationId)
    {
        // Get customers in organization and all descendants
        return Customer::forOrganizationTree($organizationId)
            ->with('organization')
            ->get();
    }
}
```

### Pattern 3: Check Organization Access

```php
class OrderController extends Controller
{
    public function store(Request $request)
    {
        $customerId = $request->input('customer_id');
        $customer = Customer::find($customerId);
        
        // Check if user can access customer's organization
        $hierarchyService = app(OrganizationHierarchyService::class);
        $visibility = auth()->user()->getOrganizationVisibility();
        
        if (!$hierarchyService->hasAccess(auth()->id(), $customer->organization_id, $visibility)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Process order...
    }
}
```

### Pattern 4: Organization Switching

```php
class OrganizationSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $organizationId = $request->input('organization_id');
        $user = auth()->user();
        
        // Validate user can access this organization
        if (!$user->canAccessOrganization($organizationId, 'tree')) {
            return response()->json(['message' => 'Cannot switch to this organization'], 403);
        }
        
        // Switch organization
        if ($user->switchOrganization($organizationId)) {
            return response()->json([
                'message' => 'Organization switched successfully',
                'organization_id' => $organizationId,
                'breadcrumb' => $user->getOrganizationBreadcrumb()
            ]);
        }
        
        return response()->json(['message' => 'Failed to switch organization'], 400);
    }
}
```

### Pattern 5: Hierarchical Reporting

```php
class SalesReportService
{
    public function generateHierarchicalReport(int $organizationId)
    {
        $hierarchyService = app(OrganizationHierarchyService::class);
        
        // Get organization and all descendants
        $organizations = $hierarchyService->getDescendantsIncludingSelf($organizationId);
        $organizationIds = $organizations->pluck('id')->toArray();
        
        // Generate report for entire organization tree
        $report = [];
        foreach ($organizations as $org) {
            $report[] = [
                'organization' => $org->getTranslation('name'),
                'level' => $org->level,
                'total_sales' => Order::forOrganization($org->id)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                'total_customers' => Customer::forOrganization($org->id)
                    ->count(),
            ];
        }
        
        return $report;
    }
}
```

## Database Schema Enhancements

### Users Table

Add organization context columns:

```sql
ALTER TABLE users ADD COLUMN organization_id BIGINT NULL;
ALTER TABLE users ADD COLUMN location_id BIGINT NULL;
ALTER TABLE users ADD COLUMN settings JSON NULL;

ALTER TABLE users ADD FOREIGN KEY (organization_id) 
    REFERENCES organizations(id) ON DELETE SET NULL;
ALTER TABLE users ADD FOREIGN KEY (location_id) 
    REFERENCES locations(id) ON DELETE SET NULL;
```

### Indexes for Performance

```sql
-- Organization hierarchy queries
CREATE INDEX idx_organizations_path ON organizations(path);
CREATE INDEX idx_organizations_level ON organizations(level);
CREATE INDEX idx_organizations_tenant_parent ON organizations(tenant_id, parent_id);

-- User organization context
CREATE INDEX idx_users_organization ON users(organization_id);
CREATE INDEX idx_users_location ON users(location_id);
CREATE INDEX idx_users_tenant_org ON users(tenant_id, organization_id);
```

## Testing

### Unit Tests

See `Modules/Organization/Tests/Unit/OrganizationHierarchyServiceTest.php` for comprehensive test coverage:

- Ancestor/descendant retrieval
- Children and siblings queries
- Tree validation (circular reference prevention)
- Organization movement
- Cache management
- Access control validation

### Feature Tests

Create feature tests for API endpoints:

```php
// Test hierarchy endpoints
public function test_it_returns_organization_descendants()
{
    $user = User::factory()->create();
    $root = Organization::factory()->create(['tenant_id' => $user->tenant_id]);
    $child = Organization::factory()->create([
        'tenant_id' => $user->tenant_id,
        'parent_id' => $root->id
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/organizations/{$root->id}/descendants");
    
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $child->id);
}
```

## Performance Considerations

1. **Caching**: All hierarchy queries are cached for 1 hour
2. **Materialized Paths**: Path column enables efficient subtree queries
3. **Indexes**: Comprehensive indexes on hierarchy columns
4. **Lazy Loading**: Use `with()` to eager load relationships
5. **Query Optimization**: Scopes use efficient WHERE IN queries

## Migration Path

### For Existing Systems

1. **Add User Organization Context**:
   ```bash
   php artisan make:migration add_organization_context_to_users_table
   ```

2. **Register Middleware**:
   ```php
   // app/Http/Kernel.php
   protected $middlewareAliases = [
       'organization.context' => \Modules\Core\Http\Middleware\OrganizationContext::class,
   ];
   ```

3. **Register Policy**:
   ```php
   // AuthServiceProvider
   protected $policies = [
       Organization::class => OrganizationPolicy::class,
   ];
   ```

4. **Apply UserOrganization Trait to User Model**:
   ```php
   class User extends Authenticatable
   {
       use UserOrganization;
   }
   ```

5. **Update Existing Entities**:
   ```php
   // Replace Organizational trait with HierarchicalOrganizational
   class Customer extends Model
   {
       use HierarchicalOrganizational; // Instead of Organizational
   }
   ```

## Security Considerations

1. **Tenant Isolation**: Always validate organization belongs to user's tenant
2. **Authorization**: Use OrganizationPolicy for all organization operations
3. **Circular References**: Service automatically prevents circular references
4. **Access Control**: Respect user visibility settings
5. **Audit Trail**: Log all organization movements and access changes

## Future Enhancements

1. **Organization Templates**: Clone organization structures
2. **Bulk Operations**: Mass organization operations with progress tracking
3. **Organization Consolidation**: Merge organizations with data migration
4. **Advanced Analytics**: Organization-based KPIs and dashboards
5. **Organization Workflows**: Approval workflows based on hierarchy
6. **Data Export**: Export organization data with descendants

## Support and Documentation

- **API Documentation**: See OpenAPI spec in `docs/api/organizations.yaml`
- **Architecture Guide**: See `ARCHITECTURE.md`
- **Domain Models**: See `DOMAIN_MODELS.md`
- **Multi-Org Architecture**: See `MULTI_ORGANIZATION_ARCHITECTURE.md`
