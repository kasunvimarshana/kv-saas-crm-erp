# GitHub Copilot Common Tasks

This guide provides step-by-step instructions for common development tasks with GitHub Copilot in the kv-saas-crm-erp repository.

## 📋 Table of Contents

- [Creating a New Module](#creating-a-new-module)
- [Adding a New Entity/Model](#adding-a-new-entitymodel)
- [Creating an API Endpoint](#creating-an-api-endpoint)
- [Adding Multi-Language Support](#adding-multi-language-support)
- [Implementing Multi-Tenant Isolation](#implementing-multi-tenant-isolation)
- [Creating a Service Class](#creating-a-service-class)
- [Implementing Repository Pattern](#implementing-repository-pattern)
- [Adding Event Listeners](#adding-event-listeners)
- [Creating Vue Components](#creating-vue-components)
- [Writing Tests](#writing-tests)
- [Adding Database Migrations](#adding-database-migrations)
- [Implementing Authorization](#implementing-authorization)

---

## Creating a New Module

### Step 1: Generate Module Structure

```bash
php artisan module:make ProductCatalog
```

### Step 2: Update module.json

```json
{
  "name": "ProductCatalog",
  "alias": "productcatalog",
  "description": "Product catalog management module",
  "keywords": ["products", "catalog", "inventory"],
  "priority": 10,
  "active": 1,
  "providers": [
    "Modules\\ProductCatalog\\Providers\\ProductCatalogServiceProvider"
  ]
}
```

### Step 3: Create Core Components

**Ask Copilot:**
> "Create a Product entity in Modules/ProductCatalog/Entities with multi-tenant support"

**Expected structure:**
```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;

class Product extends Model
{
    use Tenantable, Translatable;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'status'
    ];

    protected array $translatable = ['name', 'description'];
}
```

### Step 4: Enable and Test

```bash
# Enable module
php artisan module:enable ProductCatalog

# Run migrations
php artisan module:migrate ProductCatalog

# Verify
php artisan module:list
```

---

## Adding a New Entity/Model

### Step 1: Create Migration

```bash
php artisan make:migration create_products_table --path=Modules/ProductCatalog/Database/Migrations
```

**Ask Copilot:**
> "Create a products table migration with multi-tenant support, UUID primary key, and translatable fields"

**Expected migration:**
```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id');
    $table->string('sku')->unique();
    $table->json('name'); // Translatable
    $table->json('description'); // Translatable
    $table->decimal('price', 15, 2);
    $table->enum('status', ['active', 'inactive', 'discontinued']);
    
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
    
    $table->index('tenant_id');
    $table->index('status');
    
    $table->timestamps();
    $table->softDeletes();
});
```

### Step 2: Create Model

**Ask Copilot:**
> "Create a Product model in Modules/ProductCatalog/Entities with Tenantable and Translatable traits"

### Step 3: Create Factory

```bash
php artisan make:factory ProductFactory --model=Modules\\ProductCatalog\\Entities\\Product
```

**Ask Copilot:**
> "Create a Product factory with realistic data"

### Step 4: Create Seeder

```bash
php artisan make:seeder ProductSeeder
```

**Ask Copilot:**
> "Create a Product seeder that creates 50 products"

---

## Creating an API Endpoint

### Step 1: Create Form Requests

```bash
# Create requests directory if not exists
mkdir -p Modules/ProductCatalog/Http/Requests

# Copilot will guide you when creating files in this directory
```

**Ask Copilot:**
> "Create CreateProductRequest in Modules/ProductCatalog/Http/Requests with validation rules"

**Expected request:**
```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'unique:products,sku'],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive,discontinued']
        ];
    }
}
```

### Step 2: Create Repository

**Ask Copilot:**
> "Create ProductRepository interface and implementation following the repository pattern"

**Files to create:**
- `Modules/ProductCatalog/Repositories/Contracts/ProductRepositoryInterface.php`
- `Modules/ProductCatalog/Repositories/ProductRepository.php`

### Step 3: Create Service

**Ask Copilot:**
> "Create ProductService with createProduct, updateProduct, and deleteProduct methods"

**File:** `Modules/ProductCatalog/Services/ProductService.php`

### Step 4: Create Resource

```bash
mkdir -p Modules/ProductCatalog/Http/Resources
```

**Ask Copilot:**
> "Create ProductResource API resource transformer"

### Step 5: Create Controller

**Ask Copilot:**
> "Create ProductController with RESTful methods using ProductService and ProductResource"

**File:** `Modules/ProductCatalog/Http/Controllers/ProductController.php`

### Step 6: Define Routes

**File:** `Modules/ProductCatalog/Routes/api.php`

```php
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function () {
        Route::apiResource('products', ProductController::class);
    });
```

### Step 7: Register Repository Binding

**File:** `Modules/ProductCatalog/Providers/ProductCatalogServiceProvider.php`

```php
public function register(): void
{
    $this->app->bind(
        ProductRepositoryInterface::class,
        ProductRepository::class
    );
}
```

### Step 8: Test the Endpoint

```bash
# Run tests
php artisan test --filter=ProductControllerTest

# Check routes
php artisan route:list | grep product
```

---

## Adding Multi-Language Support

### Step 1: Add Translatable Trait to Model

```php
use Modules\Core\Traits\Translatable;

class Product extends Model
{
    use Translatable;
    
    protected array $translatable = ['name', 'description'];
}
```

### Step 2: Update Migration to Use JSON Columns

```php
Schema::create('products', function (Blueprint $table) {
    $table->json('name');        // Instead of string
    $table->json('description'); // Instead of text
});
```

### Step 3: Set Translations

```php
// In controller or service
$product->setTranslation('name', 'en', 'Product Name');
$product->setTranslation('name', 'es', 'Nombre del Producto');
$product->setTranslation('name', 'fr', 'Nom du Produit');
$product->save();
```

### Step 4: Get Translations

```php
// Get specific language
$name = $product->getTranslation('name', 'es');

// Get current locale translation
$name = $product->name; // Uses app()->getLocale()

// Get all translations
$allNames = $product->getTranslations('name');
// ['en' => 'Product Name', 'es' => 'Nombre del Producto', ...]
```

### Step 5: Update Form Request Validation

```php
public function rules(): array
{
    return [
        'name' => ['required', 'array'],
        'name.en' => ['required', 'string', 'max:255'],
        'name.es' => ['nullable', 'string', 'max:255'],
        'name.fr' => ['nullable', 'string', 'max:255'],
    ];
}
```

---

## Implementing Multi-Tenant Isolation

### Step 1: Add Tenantable Trait to Model

```php
use Modules\Core\Traits\Tenantable;

class Product extends Model
{
    use Tenantable;
}
```

### Step 2: Add tenant_id Column to Migration

```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id'); // Multi-tenancy
    
    $table->foreign('tenant_id')
        ->references('id')
        ->on('tenants')
        ->onDelete('cascade');
    
    $table->index('tenant_id'); // Performance
    
    // Other columns...
});
```

### Step 3: Apply Tenant Middleware

```php
// Routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### Step 4: Initialize Tenant Context

```php
// In middleware
public function handle($request, Closure $next)
{
    $tenantId = $request->header('X-Tenant-ID');
    
    if (!$tenantId) {
        return response()->json(['error' => 'Tenant ID required'], 400);
    }
    
    tenancy()->initialize($tenantId);
    
    return $next($request);
}
```

### Step 5: Test Tenant Isolation

```php
public function test_user_cannot_access_other_tenant_data(): void
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    tenancy()->initialize($tenant1);
    $product1 = Product::factory()->create();
    
    tenancy()->initialize($tenant2);
    
    // Should not find product from tenant1
    $this->assertNull(Product::find($product1->id));
}
```

---

## Creating a Service Class

### Step 1: Create Service File

**File:** `Modules/ProductCatalog/Services/ProductService.php`

**Ask Copilot:**
> "Create ProductService with CRUD operations following the service layer pattern"

**Expected structure:**
```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Services;

use Illuminate\Support\Facades\DB;
use Modules\ProductCatalog\Entities\Product;
use Modules\ProductCatalog\Repositories\Contracts\ProductRepositoryInterface;
use Modules\ProductCatalog\Events\ProductCreated;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function createProduct(array $data): Product
    {
        DB::beginTransaction();
        try {
            $product = $this->productRepository->create($data);
            
            event(new ProductCreated($product));
            
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateProduct(string $id, array $data): Product
    {
        DB::beginTransaction();
        try {
            $product = $this->productRepository->findById($id);
            
            if (!$product) {
                throw new \Exception("Product not found: {$id}");
            }
            
            $product = $this->productRepository->update($product, $data);
            
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteProduct(string $id): bool
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            throw new \Exception("Product not found: {$id}");
        }
        
        return $this->productRepository->delete($product);
    }
}
```

### Step 2: Register Service in Provider

```php
// Providers/ProductCatalogServiceProvider.php
public function register(): void
{
    $this->app->singleton(ProductService::class);
}
```

### Step 3: Use Service in Controller

```php
public function __construct(
    private ProductService $productService
) {}

public function store(CreateProductRequest $request)
{
    $product = $this->productService->createProduct($request->validated());
    return new ProductResource($product);
}
```

---

## Implementing Repository Pattern

### Step 1: Create Repository Interface

**File:** `Modules/ProductCatalog/Repositories/Contracts/ProductRepositoryInterface.php`

**Ask Copilot:**
> "Create ProductRepositoryInterface with CRUD and query methods"

```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\ProductCatalog\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(string $id): ?Product;
    public function findBySku(string $sku): ?Product;
    public function all(): Collection;
    public function paginate(int $perPage = 15);
    public function create(array $data): Product;
    public function update(Product $product, array $data): Product;
    public function delete(Product $product): bool;
    public function findActive(): Collection;
    public function search(string $query): Collection;
}
```

### Step 2: Create Repository Implementation

**File:** `Modules/ProductCatalog/Repositories/ProductRepository.php`

**Ask Copilot:**
> "Create ProductRepository implementing ProductRepositoryInterface"

```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\ProductCatalog\Entities\Product;
use Modules\ProductCatalog\Repositories\Contracts\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        protected Product $model
    ) {}

    public function findById(string $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function findActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('sku', 'LIKE', "%{$query}%")
            ->get();
    }
}
```

### Step 3: Register Binding

```php
// Providers/ProductCatalogServiceProvider.php
public function register(): void
{
    $this->app->bind(
        ProductRepositoryInterface::class,
        ProductRepository::class
    );
}
```

---

## Adding Event Listeners

### Step 1: Create Event

**File:** `Modules/ProductCatalog/Events/ProductCreated.php`

**Ask Copilot:**
> "Create ProductCreated event"

```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ProductCatalog\Entities\Product;

class ProductCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product
    ) {}
}
```

### Step 2: Create Listener

**File:** `Modules/ProductCatalog/Listeners/UpdateInventoryOnProductCreated.php`

**Ask Copilot:**
> "Create a queued listener for ProductCreated event"

```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\ProductCatalog\Events\ProductCreated;
use Modules\Inventory\Services\InventoryService;

class UpdateInventoryOnProductCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function handle(ProductCreated $event): void
    {
        // Create inventory record for new product
        $this->inventoryService->createInventoryForProduct($event->product);
    }
}
```

### Step 3: Register in EventServiceProvider

**File:** `Modules/ProductCatalog/Providers/EventServiceProvider.php`

```php
protected $listen = [
    ProductCreated::class => [
        UpdateInventoryOnProductCreated::class,
        LogProductCreation::class,
    ],
];
```

### Step 4: Dispatch Event

```php
// In service
event(new ProductCreated($product));
```

---

## Creating Vue Components

### Step 1: Create Component File

**File:** `resources/js/components/ProductForm.vue`

**Ask Copilot:**
> "Create a Vue 3 ProductForm component with Composition API"

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Product } from '@/types'

interface Props {
  product?: Product
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false
})

const emit = defineEmits<{
  submit: [product: Product]
  cancel: []
}>()

const formData = ref({
  sku: props.product?.sku || '',
  name: props.product?.name || { en: '', es: '', fr: '' },
  price: props.product?.price || 0,
  status: props.product?.status || 'active'
})

const isValid = computed(() => {
  return formData.value.sku !== '' 
    && formData.value.name.en !== ''
    && formData.value.price > 0
})

const handleSubmit = () => {
  if (!isValid.value) return
  emit('submit', formData.value as Product)
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="product-form">
    <div class="form-field">
      <label>SKU</label>
      <input 
        v-model="formData.sku" 
        :disabled="readonly"
        required
      />
    </div>
    
    <div class="form-field">
      <label>Name (English)</label>
      <input 
        v-model="formData.name.en" 
        :disabled="readonly"
        required
      />
    </div>
    
    <div class="form-field">
      <label>Price</label>
      <input 
        v-model.number="formData.price" 
        type="number" 
        step="0.01"
        :disabled="readonly"
        required
      />
    </div>
    
    <div class="form-actions" v-if="!readonly">
      <button type="submit" :disabled="!isValid">
        Save Product
      </button>
      <button type="button" @click="emit('cancel')">
        Cancel
      </button>
    </div>
  </form>
</template>

<style scoped>
.product-form {
  max-width: 600px;
}

.form-field {
  margin-bottom: 1rem;
}

.form-field label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.form-field input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}
</style>
```

### Step 2: Create Composable (if needed)

**File:** `resources/js/composables/useProducts.ts`

**Ask Copilot:**
> "Create useProducts composable for product API operations"

---

## Writing Tests

### Step 1: Create Feature Test

```bash
php artisan make:test Feature/ProductControllerTest
```

**Ask Copilot:**
> "Create feature tests for ProductController with CRUD operations"

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ProductCatalog\Entities\Product;
use Modules\Core\Entities\User;
use Modules\Core\Entities\Tenant;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);
        
        $this->user = User::factory()->create();
    }

    public function test_it_creates_product_with_valid_data(): void
    {
        // Arrange
        $data = [
            'sku' => 'PROD-001',
            'name' => ['en' => 'Test Product'],
            'price' => 99.99,
            'status' => 'active'
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['sku' => 'PROD-001']);
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/products', []);
        $response->assertStatus(401);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'name', 'price']);
    }
}
```

### Step 2: Create Unit Test

```bash
php artisan make:test Unit/Services/ProductServiceTest --unit
```

**Ask Copilot:**
> "Create unit tests for ProductService with mocked dependencies"

---

## Adding Database Migrations

### Step 1: Create Migration File

```bash
php artisan make:migration create_products_table --path=Modules/ProductCatalog/Database/Migrations
```

### Step 2: Define Schema

**Ask Copilot:**
> "Create products table migration with UUID, multi-tenant support, and proper indexes"

### Step 3: Run Migration

```bash
# Run migration
php artisan migrate

# Or for specific module
php artisan module:migrate ProductCatalog

# Rollback if needed
php artisan migrate:rollback

# Check status
php artisan migrate:status
```

---

## Implementing Authorization

### Step 1: Create Policy

```bash
php artisan make:policy ProductPolicy --model=Modules\\ProductCatalog\\Entities\\Product
```

**Ask Copilot:**
> "Create ProductPolicy with viewAny, view, create, update, delete methods"

```php
<?php

declare(strict_types=1);

namespace Modules\ProductCatalog\Policies;

use Modules\Core\Entities\User;
use Modules\ProductCatalog\Entities\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view-products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('view-products');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create-products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('edit-products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('delete-products')
            && $product->status !== 'discontinued';
    }
}
```

### Step 2: Register Policy

**File:** `app/Providers/AuthServiceProvider.php`

```php
protected $policies = [
    Product::class => ProductPolicy::class,
];
```

### Step 3: Use in Controller

```php
public function update(UpdateProductRequest $request, Product $product)
{
    $this->authorize('update', $product);
    
    $product = $this->productService->updateProduct(
        $product->id, 
        $request->validated()
    );
    
    return new ProductResource($product);
}
```

### Step 4: Use in Form Request

```php
public function authorize(): bool
{
    return $this->user()->can('create', Product::class);
}
```

---

## Quick Command Reference

```bash
# Module commands
php artisan module:make ModuleName
php artisan module:enable ModuleName
php artisan module:list

# Code generation
php artisan make:controller Modules/Module/Http/Controllers/NameController
php artisan make:model Modules/Module/Entities/Name
php artisan make:migration create_table --path=Modules/Module/Database/Migrations
php artisan make:factory NameFactory
php artisan make:seeder NameSeeder
php artisan make:request CreateNameRequest
php artisan make:policy NamePolicy --model=Name
php artisan make:test Feature/NameControllerTest
php artisan make:test Unit/Services/NameServiceTest --unit

# Database
php artisan migrate
php artisan module:migrate ModuleName
php artisan db:seed
php artisan module:seed ModuleName

# Testing
php artisan test
php artisan test --testsuite=Feature
php artisan test --filter=ProductControllerTest
php artisan test --coverage

# Code quality
./vendor/bin/pint
./vendor/bin/pint --test

# Caching
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Development
php artisan serve
npm run dev
npm run build
```

---

**Need help?** Check [COPILOT_TROUBLESHOOTING.md](COPILOT_TROUBLESHOOTING.md) for common issues and solutions!
