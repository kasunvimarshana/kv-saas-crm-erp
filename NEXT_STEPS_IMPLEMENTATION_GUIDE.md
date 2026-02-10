# Next Steps: Implementation Guide

**Based on Comprehensive Audit 2026**  
**Date**: February 10, 2026  
**Priority**: High-Impact Actions  

---

## Overview

This guide provides actionable steps to complete the ERP/CRM SaaS platform based on the comprehensive audit findings. The backend is **95% complete** and production-ready. Focus areas: Frontend (5%), Testing (10%), and Documentation (25%).

---

## Phase 1: API Documentation (3-5 Days) ⚡ HIGH PRIORITY

### Goal
Add OpenAPI 3.1 annotations to all controllers and generate interactive Swagger documentation.

### Steps

#### 1.1 Install and Configure L5-Swagger

**Status**: ✅ Already installed

**Verify Configuration:**
```bash
# Check if l5-swagger is configured
cat config/l5-swagger.php
```

**Generate Base Documentation:**
```bash
php artisan l5-swagger:generate
```

#### 1.2 Add OpenAPI Annotations to Controllers

**Example: Customer Controller**

```php
<?php

namespace Modules\Sales\Http\Controllers\Api;

use Modules\Sales\Http\Controllers\Controller;
use Modules\Sales\Http\Requests\StoreCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use Modules\Sales\Services\CustomerService;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management endpoints"
 * )
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers.
     *
     * @OA\Get(
     *     path="/api/v1/customers",
     *     operationId="getCustomersList",
     *     tags={"Customers"},
     *     summary="Get list of customers",
     *     description="Returns list of customers for the current tenant",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="filter[name]",
     *         in="query",
     *         description="Filter by customer name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field (prefix with - for descending)",
     *         required=false,
     *         @OA\Schema(type="string", example="-created_at")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Customer")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        $customers = $this->customerService->getAllCustomers();
        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created customer.
     *
     * @OA\Post(
     *     path="/api/v1/customers",
     *     operationId="storeCustomer",
     *     tags={"Customers"},
     *     summary="Create new customer",
     *     description="Creates a new customer for the current tenant",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Customer data",
     *         @OA\JsonContent(
     *             required={"name","email","type"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="type", type="string", enum={"individual", "business"}, example="individual"),
     *             @OA\Property(property="tax_id", type="string", example="123456789"),
     *             @OA\Property(
     *                 property="address",
     *                 type="object",
     *                 @OA\Property(property="line1", type="string"),
     *                 @OA\Property(property="city", type="string"),
     *                 @OA\Property(property="postal_code", type="string"),
     *                 @OA\Property(property="country", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Customer"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->customerService->createCustomer($request->validated());
        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    // ... additional methods with annotations
}
```

**Add Schema Definition:**

```php
/**
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     title="Customer",
 *     required={"id", "name", "email", "type"},
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="type", type="string", enum={"individual", "business"}),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "blocked"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Customer extends Model
{
    // ... model code
}
```

#### 1.3 Controllers to Document (Priority Order)

1. **Sales Module** (4 controllers × 5 methods = 20 endpoints)
   - CustomerController
   - LeadController
   - SalesOrderController
   - SalesOrderLineController

2. **Inventory Module** (6 controllers × 5 methods = 30 endpoints)
   - ProductController
   - ProductCategoryController
   - WarehouseController
   - StockLevelController
   - StockLocationController
   - StockMovementController

3. **Accounting Module** (6 controllers × 5 methods = 30 endpoints)
   - AccountController
   - InvoiceController
   - JournalEntryController
   - PaymentController
   - FiscalPeriodController

4. **HR Module** (8 controllers × 5 methods = 40 endpoints)
5. **Procurement Module** (6 controllers × 5 methods = 30 endpoints)

#### 1.4 Generate and Verify Documentation

```bash
# Generate Swagger documentation
php artisan l5-swagger:generate

# View documentation
# Visit: http://localhost:8000/api/documentation
```

**Checklist:**
- [ ] All endpoints documented
- [ ] Request schemas defined
- [ ] Response schemas defined
- [ ] Authentication documented
- [ ] Error responses included
- [ ] Examples provided

---

## Phase 2: Comprehensive Testing (1-2 Weeks) 🧪 CRITICAL

### Goal
Achieve 80%+ test coverage with unit, feature, and integration tests.

### Testing Strategy

#### 2.1 Core Module Tests (Foundation)

**File**: `Modules/Core/Tests/Unit/Traits/TenantalbleTest.php`

```php
<?php

namespace Modules\Core\Tests\Unit\Traits;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Tests\Fixtures\TenantableModel;
use Modules\Tenancy\Entities\Tenant;
use Illuminate\Support\Facades\Session;

class TenantableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_automatically_assigns_tenant_id_on_creation(): void
    {
        $tenant = Tenant::factory()->create();
        Session::put('tenant_id', $tenant->id);

        $model = TenantableModel::create(['name' => 'Test']);

        $this->assertEquals($tenant->id, $model->tenant_id);
    }

    public function test_it_scopes_queries_to_current_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create models for tenant 1
        Session::put('tenant_id', $tenant1->id);
        TenantableModel::create(['name' => 'Tenant 1 Model 1']);
        TenantableModel::create(['name' => 'Tenant 1 Model 2']);

        // Create models for tenant 2
        Session::put('tenant_id', $tenant2->id);
        TenantableModel::create(['name' => 'Tenant 2 Model 1']);

        // Query as tenant 1
        Session::put('tenant_id', $tenant1->id);
        $models = TenantableModel::all();

        $this->assertCount(2, $models);
        $this->assertTrue($models->every(fn($m) => $m->tenant_id === $tenant1->id));
    }

    public function test_it_allows_admin_to_query_without_tenant_scope(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Session::put('tenant_id', $tenant1->id);
        TenantableModel::create(['name' => 'Model 1']);

        Session::put('tenant_id', $tenant2->id);
        TenantableModel::create(['name' => 'Model 2']);

        // Admin query without tenant scope
        $allModels = TenantableModel::withoutTenancy()->get();

        $this->assertCount(2, $allModels);
    }

    public function test_it_queries_specific_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Session::put('tenant_id', $tenant1->id);
        TenantableModel::create(['name' => 'Tenant 1 Model']);

        Session::put('tenant_id', $tenant2->id);
        TenantableModel::create(['name' => 'Tenant 2 Model']);

        // Query specific tenant
        $tenant1Models = TenantableModel::forTenant($tenant1->id)->get();

        $this->assertCount(1, $tenant1Models);
        $this->assertEquals($tenant1->id, $tenant1Models->first()->tenant_id);
    }
}
```

**Create Test Fixture:**

`Modules/Core/Tests/Fixtures/TenantableModel.php`
```php
<?php

namespace Modules\Core\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\Tenantable;

class TenantableModel extends Model
{
    use Tenantable;

    protected $table = 'tenantable_test_models';
    protected $fillable = ['name', 'tenant_id'];
}
```

**Migration:**
```php
Schema::create('tenantable_test_models', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('tenant_id');
    $table->string('name');
    $table->timestamps();
});
```

#### 2.2 Service Layer Unit Tests

**File**: `Modules/Sales/Tests/Unit/CustomerServiceTest.php`

```php
<?php

namespace Modules\Sales\Tests\Unit;

use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Services\CustomerService;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Entities\Customer;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerService $service;
    protected $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(CustomerRepositoryInterface::class);
        $this->service = new CustomerService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_customer_with_valid_data(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'individual',
        ];

        $customer = new Customer($data);
        $customer->id = 'uuid-123';

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($customer);

        $result = $this->service->createCustomer($data);

        $this->assertInstanceOf(Customer::class, $result);
        $this->assertEquals('John Doe', $result->name);
    }

    public function test_it_throws_exception_for_duplicate_email(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email address already in use');

        $data = ['email' => 'existing@example.com'];

        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('existing@example.com')
            ->andReturn(new Customer());

        $this->service->createCustomer($data);
    }
}
```

#### 2.3 API Feature Tests

**File**: `Modules/Sales/Tests/Feature/CustomerApiTest.php`

```php
<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sales\Entities\Customer;
use Modules\Tenancy\Entities\Tenant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        Sanctum::actingAs($this->user);
        session(['tenant_id' => $this->tenant->id]);
    }

    public function test_it_lists_customers_with_authentication(): void
    {
        Customer::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'type', 'created_at']
                ],
                'meta' => ['current_page', 'total'],
                'links'
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_it_creates_customer_with_valid_data(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'individual',
        ];

        $response = $this->postJson('/api/v1/customers', $data);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']])
            ->assertJson(['data' => ['name' => 'John Doe']]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_it_returns_422_with_invalid_data(): void
    {
        $response = $this->postJson('/api/v1/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'type']);
    }

    public function test_it_prevents_cross_tenant_access(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->getJson("/api/v1/customers/{$otherCustomer->id}");

        $response->assertStatus(404);
    }

    public function test_it_requires_authentication(): void
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/customers');

        $response->assertStatus(401);
    }

    public function test_it_enforces_authorization(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($userWithoutPermission);

        $response = $this->deleteJson("/api/v1/customers/uuid-123");

        $response->assertStatus(403);
    }
}
```

#### 2.4 Integration Tests (Cross-Module)

**File**: `tests/Integration/SalesOrderIntegrationTest.php`

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Events\SalesOrderConfirmed;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Inventory\Entities\StockLevel;

class SalesOrderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_confirmation_creates_accounting_entry(): void
    {
        Event::fake();

        $order = SalesOrder::factory()->create(['status' => 'draft']);

        $order->confirm();

        Event::assertDispatched(SalesOrderConfirmed::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });

        // Verify accounting entry created
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => SalesOrder::class,
            'reference_id' => $order->id,
        ]);
    }

    public function test_order_confirmation_reserves_inventory(): void
    {
        $product = Product::factory()->create();
        $stockLevel = StockLevel::factory()->create([
            'product_id' => $product->id,
            'available_quantity' => 100,
        ]);

        $order = SalesOrder::factory()->create();
        $order->lines()->create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $order->confirm();

        $stockLevel->refresh();
        $this->assertEquals(90, $stockLevel->available_quantity);
    }
}
```

### Test Execution Plan

**Week 1: Core & Service Tests**
- [ ] Day 1-2: Core module tests (traits, base classes)
- [ ] Day 3-4: Service layer unit tests (Sales, Inventory)
- [ ] Day 5: Service layer unit tests (Accounting, HR, Procurement)

**Week 2: API & Integration Tests**
- [ ] Day 1-2: API feature tests (Sales, Inventory)
- [ ] Day 3-4: API feature tests (Accounting, HR, Procurement)
- [ ] Day 5: Integration tests (cross-module workflows)

**Target:** 
- 24 service tests
- 36 repository tests  
- 100+ API feature tests
- 10+ integration tests
- **Total: 170+ tests**
- **Coverage: 80%+**

---

## Phase 3: Frontend MVP (2-4 Weeks) 🎨 HIGH VALUE

### Goal
Build functional Vue 3 frontend with authentication, dashboard, and key module interfaces.

### 3.1 Authentication UI (Week 1)

#### Login Page

**File**: `resources/js/pages/auth/Login.vue`

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const email = ref('')
const password = ref('')
const remember = ref(false)
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)

const login = async () => {
  isLoading.value = true
  errors.value = {}
  
  try {
    const response = await axios.post('/api/v1/auth/login', {
      email: email.value,
      password: password.value,
      remember: remember.value
    })
    
    // Store token
    localStorage.setItem('auth_token', response.data.token)
    
    // Redirect to dashboard
    router.push('/dashboard')
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: ['An error occurred. Please try again.'] }
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="login">
        <!-- Email Field -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">
            Email address
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            :class="{ 'border-red-500': errors.email }"
          />
          <p v-if="errors.email" class="mt-1 text-sm text-red-600">
            {{ errors.email[0] }}
          </p>
        </div>

        <!-- Password Field -->
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">
            Password
          </label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
            :class="{ 'border-red-500': errors.password }"
          />
          <p v-if="errors.password" class="mt-1 text-sm text-red-600">
            {{ errors.password[0] }}
          </p>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember"
              v-model="remember"
              type="checkbox"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label for="remember" class="ml-2 block text-sm text-gray-900">
              Remember me
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
              Forgot your password?
            </a>
          </div>
        </div>

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
          >
            <span v-if="!isLoading">Sign in</span>
            <span v-else>Signing in...</span>
          </button>
        </div>

        <!-- General Error -->
        <div v-if="errors.general" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ errors.general[0] }}</p>
        </div>
      </form>
    </div>
  </div>
</template>
```

### 3.2 Generic Components

#### DataTable Component

**File**: `resources/js/components/DataTable.vue`

```vue
<script setup lang="ts" generic="T extends Record<string, any>">
import { computed, ref } from 'vue'

interface Column {
  key: string
  label: string
  sortable?: boolean
  formatter?: (value: any, row: T) => string
}

interface Props {
  columns: Column[]
  data: T[]
  loading?: boolean
  sortBy?: string
  sortDirection?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  sortBy: '',
  sortDirection: 'asc'
})

const emit = defineEmits<{
  sort: [column: string, direction: 'asc' | 'desc']
  rowClick: [row: T]
}>()

const currentSort = ref(props.sortBy)
const currentDirection = ref(props.sortDirection)

const handleSort = (column: Column) => {
  if (!column.sortable) return
  
  if (currentSort.value === column.key) {
    currentDirection.value = currentDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    currentSort.value = column.key
    currentDirection.value = 'asc'
  }
  
  emit('sort', currentSort.value, currentDirection.value)
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            :class="{ 'cursor-pointer hover:bg-gray-100': column.sortable }"
            @click="handleSort(column)"
          >
            <div class="flex items-center">
              {{ column.label }}
              <span v-if="column.sortable && currentSort === column.key" class="ml-2">
                {{ currentDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </div>
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-if="loading">
          <td :colspan="columns.length" class="px-6 py-4 text-center text-gray-500">
            Loading...
          </td>
        </tr>
        <tr
          v-for="(row, index) in data"
          :key="index"
          class="hover:bg-gray-50 cursor-pointer"
          @click="emit('rowClick', row)"
        >
          <td
            v-for="column in columns"
            :key="column.key"
            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
          >
            {{ column.formatter ? column.formatter(row[column.key], row) : row[column.key] }}
          </td>
        </tr>
        <tr v-if="!loading && data.length === 0">
          <td :colspan="columns.length" class="px-6 py-4 text-center text-gray-500">
            No data available
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

### 3.3 Module-Specific UI

#### Customer List Page

**File**: `resources/js/pages/sales/CustomerList.vue`

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/components/DataTable.vue'
import axios from 'axios'

interface Customer {
  id: string
  name: string
  email: string
  type: string
  status: string
  created_at: string
}

const router = useRouter()
const customers = ref<Customer[]>([])
const isLoading = ref(false)

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  {
    key: 'created_at',
    label: 'Created',
    sortable: true,
    formatter: (value: string) => new Date(value).toLocaleDateString()
  }
]

const fetchCustomers = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('/api/v1/customers')
    customers.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch customers:', error)
  } finally {
    isLoading.value = false
  }
}

const handleRowClick = (customer: Customer) => {
  router.push(`/customers/${customer.id}`)
}

const handleSort = (column: string, direction: 'asc' | 'desc') => {
  // Implement sorting logic
  console.log('Sort:', column, direction)
}

onMounted(() => {
  fetchCustomers()
})
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Customers</h1>
      <button
        class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700"
        @click="router.push('/customers/create')"
      >
        Add Customer
      </button>
    </div>

    <DataTable
      :columns="columns"
      :data="customers"
      :loading="isLoading"
      @sort="handleSort"
      @row-click="handleRowClick"
    />
  </div>
</template>
```

### Frontend Implementation Checklist

**Week 1-2: Authentication & Layout**
- [ ] Login page
- [ ] Register page (if needed)
- [ ] Password reset
- [ ] Main layout with sidebar
- [ ] Top navigation bar
- [ ] User menu dropdown
- [ ] Tenant selector

**Week 3: Generic Components**
- [ ] DataTable component
- [ ] Form builder/components
- [ ] Modal/Dialog component
- [ ] Dropdown/Select component
- [ ] Date picker
- [ ] File uploader
- [ ] Loading states
- [ ] Error handling

**Week 4: Sales Module UI**
- [ ] Customer list
- [ ] Customer create/edit form
- [ ] Lead list
- [ ] Lead pipeline (kanban)
- [ ] Sales order list
- [ ] Sales order form

---

## Phase 4: Production Readiness (4-6 Weeks)

### 4.1 Security Enhancements

- [ ] Add rate limiting to API routes
- [ ] Implement two-factor authentication (2FA)
- [ ] Add password policies
- [ ] Implement API key management
- [ ] Security audit
- [ ] Penetration testing

### 4.2 Performance Optimization

- [ ] Add caching layer (Redis)
- [ ] Optimize database queries (N+1 prevention)
- [ ] Add database indexes
- [ ] Implement query result caching
- [ ] Optimize API responses
- [ ] Frontend lazy loading

### 4.3 Deployment Setup

- [ ] Configure CI/CD pipeline
- [ ] Docker image optimization
- [ ] Environment configuration
- [ ] Database migration strategy
- [ ] Backup and recovery plan
- [ ] Monitoring and alerting

---

## Summary Timeline

| Phase | Duration | Priority | Effort |
|-------|----------|----------|--------|
| **Phase 1: API Documentation** | 3-5 days | ⚡ High | Low |
| **Phase 2: Testing** | 1-2 weeks | 🧪 Critical | Medium |
| **Phase 3: Frontend MVP** | 2-4 weeks | 🎨 High | High |
| **Phase 4: Production** | 4-6 weeks | 🚀 Medium | High |

**Total Estimated Time**: 8-12 weeks to full production

---

## Key Success Metrics

- [ ] API Documentation: 100% of endpoints documented
- [ ] Test Coverage: 80%+ with CI passing
- [ ] Frontend: All CRUD operations functional
- [ ] Performance: < 200ms API response time
- [ ] Security: Zero critical vulnerabilities
- [ ] Deployment: Automated with rollback capability

---

**Last Updated**: February 10, 2026  
**Status**: Ready for Implementation  
**Next Action**: Begin Phase 1 - API Documentation
