# GitHub Copilot Troubleshooting Guide

This guide helps you resolve common issues when working with GitHub Copilot in the kv-saas-crm-erp repository.

## 🔍 Issue Categories

- [Copilot Suggesting Wrong Patterns](#copilot-suggesting-wrong-patterns)
- [Third-Party Package Suggestions](#third-party-package-suggestions)
- [Code Style Issues](#code-style-issues)
- [Testing Problems](#testing-problems)
- [Multi-Tenant Issues](#multi-tenant-issues)
- [Module Creation Issues](#module-creation-issues)
- [Build and Deployment Issues](#build-and-deployment-issues)

---

## Copilot Suggesting Wrong Patterns

### Problem: Copilot suggests putting business logic in controllers

**What you see:**
```php
public function store(Request $request) {
    DB::beginTransaction();
    try {
        $customer = Customer::create($request->validated());
        // ... 50 lines of business logic
    }
}
```

**Solution:**
1. **Reject the suggestion** - Press `Esc`
2. **Create a Service class** first
3. **Ask Copilot specifically**: "Create a CustomerService following the service layer pattern"
4. **Then update controller**: "Use CustomerService in CustomerController"

**Correct pattern:**
```php
// Controller (thin)
public function store(CreateCustomerRequest $request) {
    $customer = $this->customerService->createCustomer($request->validated());
    return new CustomerResource($customer);
}

// Service (business logic)
class CustomerService {
    public function createCustomer(array $data): Customer {
        DB::beginTransaction();
        try {
            // Business logic here
        }
    }
}
```

### Problem: Copilot suggests direct Eloquent queries in controllers

**What you see:**
```php
$customers = Customer::where('status', 'active')->get();
```

**Solution:**
1. **Create repository interface and implementation** first
2. **Inject repository** in controller constructor
3. **Use repository method**: `$this->customerRepository->findActive()`

**Correct pattern:**
```php
// Repository Interface
interface CustomerRepositoryInterface {
    public function findActive(): Collection;
}

// Repository Implementation
class CustomerRepository implements CustomerRepositoryInterface {
    public function findActive(): Collection {
        return Customer::where('status', 'active')->get();
    }
}

// Controller
public function __construct(
    private CustomerRepositoryInterface $customerRepository
) {}

public function index() {
    return CustomerResource::collection(
        $this->customerRepository->findActive()
    );
}
```

---

## Third-Party Package Suggestions

### Problem: Copilot suggests installing spatie/laravel-permission

**What you see:**
```bash
composer require spatie/laravel-permission
```

**Solution:**
❌ **DO NOT install third-party packages!**

✅ **Use native Laravel Gates & Policies:**

```php
// Define a Gate
Gate::define('edit-customer', function (User $user, Customer $customer) {
    return $user->id === $customer->user_id;
});

// Use in controller
public function update(Request $request, Customer $customer) {
    $this->authorize('edit-customer', $customer);
    // Update logic
}

// Or create a Policy
php artisan make:policy CustomerPolicy --model=Customer
```

**Reference:** See `NATIVE_FEATURES.md` for complete native implementations.

### Problem: Copilot suggests spatie/laravel-translatable

**Solution:**
✅ **Use native `Translatable` trait:**

```php
// Model
use Modules\Core\Traits\Translatable;

class Product extends Model {
    use Translatable;
    
    protected array $translatable = ['name', 'description'];
}

// Usage
$product->setTranslation('name', 'en', 'Product Name');
$product->setTranslation('name', 'es', 'Nombre del Producto');
$name = $product->getTranslation('name', 'es'); // "Nombre del Producto"
```

### Problem: Copilot suggests stancl/tenancy

**Solution:**
✅ **Use native `Tenantable` trait:**

```php
// Model
use Modules\Core\Traits\Tenantable;

class Customer extends Model {
    use Tenantable;
}

// Middleware
class TenantMiddleware {
    public function handle($request, Closure $next) {
        $tenantId = $request->header('X-Tenant-ID');
        tenancy()->initialize($tenantId);
        return $next($request);
    }
}
```

**Reference:** See `NATIVE_FEATURES.md` section on Multi-Tenancy.

---

## Code Style Issues

### Problem: Code doesn't pass Laravel Pint checks

**What you see:**
```bash
$ ./vendor/bin/pint
  ── PHP_CodeSniffer Violations ──────────────────────────
  51 errors found
```

**Solution:**

```bash
# Auto-fix most issues
./vendor/bin/pint

# Check without fixing (for CI/CD)
./vendor/bin/pint --test

# Fix specific file
./vendor/bin/pint path/to/file.php
```

**Common issues Copilot might create:**

1. **Missing `declare(strict_types=1);`**
   ```php
   <?php
   
   declare(strict_types=1);  // ← Always add this
   
   namespace Modules\Sales\Services;
   ```

2. **Missing type hints**
   ```php
   // Wrong
   public function create($data) { }
   
   // Correct
   public function create(array $data): Customer { }
   ```

3. **Incorrect spacing**
   - Run `./vendor/bin/pint` to auto-fix

### Problem: Copilot uses Options API for Vue components

**What you see:**
```vue
<script>
export default {
  data() {
    return {
      customers: []
    }
  }
}
</script>
```

**Solution:**
✅ **Always use Composition API with `<script setup>`:**

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const customers = ref([])
const activeCustomers = computed(() => 
  customers.value.filter(c => c.status === 'active')
)

onMounted(() => {
  // Fetch customers
})
</script>
```

---

## Testing Problems

### Problem: Tests fail after creating new code

**What you see:**
```bash
$ php artisan test
FAILED  Tests\Feature\CustomerControllerTest > it creates customer
```

**Solution:**

1. **Check test follows AAA pattern:**
   ```php
   public function test_it_creates_customer(): void
   {
       // Arrange
       $data = Customer::factory()->make()->toArray();
       
       // Act
       $response = $this->postJson('/api/v1/customers', $data);
       
       // Assert
       $response->assertStatus(201);
       $this->assertDatabaseHas('customers', ['email' => $data['email']]);
   }
   ```

2. **Use RefreshDatabase trait:**
   ```php
   use Illuminate\Foundation\Testing\RefreshDatabase;
   
   class CustomerControllerTest extends TestCase
   {
       use RefreshDatabase;
   }
   ```

3. **Check factories are defined:**
   ```bash
   # Create factory if missing
   php artisan make:factory CustomerFactory --model=Customer
   ```

### Problem: Low test coverage

**What you see:**
```bash
$ php artisan test --coverage
Coverage: 45%  ← Too low! Target is 80%+
```

**Solution:**

1. **Create missing tests:**
   ```bash
   # Unit tests
   php artisan make:test Unit/Services/CustomerServiceTest --unit
   
   # Feature tests
   php artisan make:test Feature/CustomerControllerTest
   ```

2. **Test all CRUD operations:**
   - Create (POST)
   - Read (GET one, GET all)
   - Update (PUT/PATCH)
   - Delete (DELETE)
   - List with filters
   - Authorization checks

3. **Test edge cases:**
   - Invalid data
   - Unauthorized access
   - Not found errors
   - Validation failures

---

## Multi-Tenant Issues

### Problem: Data leaking between tenants

**What you see:**
- Tenant A can see Tenant B's data
- Queries return data from all tenants

**Solution:**

1. **Add `Tenantable` trait to model:**
   ```php
   use Modules\Core\Traits\Tenantable;
   
   class Customer extends Model {
       use Tenantable;  // ← Adds automatic tenant filtering
   }
   ```

2. **Add tenant_id to migrations:**
   ```php
   Schema::create('customers', function (Blueprint $table) {
       $table->uuid('id')->primary();
       $table->uuid('tenant_id');  // ← Required for multi-tenancy
       
       $table->foreign('tenant_id')
           ->references('id')
           ->on('tenants')
           ->onDelete('cascade');
       
       $table->index('tenant_id');  // ← Important for performance
   });
   ```

3. **Initialize tenant context:**
   ```php
   // In middleware
   tenancy()->initialize($tenantId);
   
   // Or manually
   Customer::withoutGlobalScope('tenant')->get(); // Access all tenants
   ```

### Problem: Tenant context not set

**What you see:**
```
RuntimeException: Tenant context not initialized
```

**Solution:**

1. **Check middleware is applied:**
   ```php
   // routes/api.php
   Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
       Route::apiResource('customers', CustomerController::class);
   });
   ```

2. **Set tenant ID in request:**
   ```bash
   # HTTP Header
   X-Tenant-ID: tenant-uuid-here
   ```

3. **Verify tenant exists:**
   ```php
   $tenant = Tenant::find($tenantId);
   if (!$tenant) {
       throw new TenantNotFoundException();
   }
   ```

---

## Module Creation Issues

### Problem: Module not being recognized

**What you see:**
```bash
$ php artisan module:list
# NewModule not in list
```

**Solution:**

1. **Check module.json exists:**
   ```json
   {
     "name": "NewModule",
     "alias": "newmodule",
     "description": "Description here",
     "active": 1,
     "providers": [
       "Modules\\NewModule\\Providers\\NewModuleServiceProvider"
     ]
   }
   ```

2. **Enable the module:**
   ```bash
   php artisan module:enable NewModule
   ```

3. **Clear config cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Check service provider is registered:**
   ```php
   // Modules/NewModule/Providers/NewModuleServiceProvider.php
   public function register(): void {
       $this->app->register(RouteServiceProvider::class);
   }
   ```

### Problem: Module routes not working

**What you see:**
```bash
404 Not Found when accessing /api/v1/newmodule/...
```

**Solution:**

1. **Check routes are defined:**
   ```php
   // Modules/NewModule/Routes/api.php
   Route::prefix('v1')->group(function () {
       Route::apiResource('newmodule', NewModuleController::class);
   });
   ```

2. **Verify RouteServiceProvider loads routes:**
   ```php
   public function boot(): void {
       Route::middleware('api')
           ->prefix('api')
           ->group(module_path('NewModule', 'Routes/api.php'));
   }
   ```

3. **Check route list:**
   ```bash
   php artisan route:list | grep newmodule
   ```

---

## Build and Deployment Issues

### Problem: Frontend build fails

**What you see:**
```bash
$ npm run build
ERROR: Cannot find module '@/components/...'
```

**Solution:**

1. **Check alias configuration:**
   ```javascript
   // vite.config.js
   export default defineConfig({
       resolve: {
           alias: {
               '@': '/resources/js'
           }
       }
   });
   ```

2. **Install dependencies:**
   ```bash
   npm install
   npm run dev  # Test in development mode first
   ```

3. **Check import paths:**
   ```javascript
   // Use '@' alias
   import CustomerForm from '@/components/CustomerForm.vue'
   
   // Not relative paths
   import CustomerForm from '../../components/CustomerForm.vue'
   ```

### Problem: Docker container won't start

**What you see:**
```bash
$ docker-compose up -d
ERROR: ...
```

**Solution:**

1. **Check logs:**
   ```bash
   docker-compose logs app
   ```

2. **Common issues:**
   - `.env` file missing → `cp .env.example .env`
   - Port conflicts → Change ports in `docker-compose.yml`
   - Permission issues → `chmod -R 777 storage bootstrap/cache`

3. **Rebuild containers:**
   ```bash
   docker-compose down
   docker-compose build --no-cache
   docker-compose up -d
   ```

---

## Quick Fixes Reference

### "Copilot suggests wrong pattern"
→ Reject suggestion, read pattern-specific instruction file, ask Copilot with specific prompt

### "Third-party package suggested"
→ Check `NATIVE_FEATURES.md` for native implementation, reject package suggestion

### "Code style issues"
→ Run `./vendor/bin/pint`

### "Tests failing"
→ Check AAA pattern, use `RefreshDatabase`, verify factories exist

### "Multi-tenant data leak"
→ Add `Tenantable` trait, add `tenant_id` column, add index

### "Module not recognized"
→ Check `module.json`, enable module, clear cache

### "Routes not working"
→ Check `Routes/api.php`, verify `RouteServiceProvider`, run `route:list`

### "Build fails"
→ Check `vite.config.js`, run `npm install`, verify import paths

---

## Getting Additional Help

1. **Check pattern-specific instructions:**
   - `.github/instructions/api-controllers.instructions.md`
   - `.github/instructions/service-layer.instructions.md`
   - `.github/instructions/repository-pattern.instructions.md`
   - And others...

2. **Review main instructions:**
   - `.github/copilot-instructions.md`

3. **Search documentation:**
   - `DOCUMENTATION_INDEX.md`
   - `NATIVE_FEATURES.md`
   - `MODULE_DEVELOPMENT_GUIDE.md`

4. **Look at existing code:**
   - `Modules/Sales/` - Reference implementation
   - `Modules/Core/` - Shared utilities

5. **Run validation:**
   ```bash
   ./vendor/bin/pint         # Code style
   php artisan test          # Tests
   php artisan route:list    # Routes
   php artisan migrate:status # Migrations
   ```

---

**Still stuck?** Review the error message carefully, check the relevant pattern instruction file, and ensure you're following native implementations!
