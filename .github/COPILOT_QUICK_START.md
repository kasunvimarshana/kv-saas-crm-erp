# GitHub Copilot Quick Start Guide

This guide helps you get started with GitHub Copilot in the kv-saas-crm-erp repository.

## 🚀 First Steps

### 1. Understand the Project Structure

```
kv-saas-crm-erp/
├── .github/
│   ├── copilot-instructions.md      # Main instructions (read this first!)
│   └── instructions/                 # Pattern-specific guidelines
├── Modules/                          # Business modules (your main workspace)
│   ├── Core/                        # Shared functionality
│   ├── Sales/                       # Sales & CRM
│   ├── Inventory/                   # Inventory management
│   ├── Accounting/                  # Finance & accounting
│   └── {YourModule}/               # Create new modules here
├── ARCHITECTURE.md                   # System architecture
├── NATIVE_FEATURES.md               # Native implementations guide
└── MODULE_DEVELOPMENT_GUIDE.md      # Module development guide
```

### 2. Read Essential Documentation

**Before writing any code, read these (in order):**

1. **[.github/copilot-instructions.md](.github/copilot-instructions.md)** - Main guidelines (15 min read)
2. **[NATIVE_FEATURES.md](../NATIVE_FEATURES.md)** - Native implementations (10 min read)
3. **[MODULE_DEVELOPMENT_GUIDE.md](../MODULE_DEVELOPMENT_GUIDE.md)** - Module creation (20 min read)

### 3. Set Up Your Environment

```bash
# Clone the repository
git clone https://github.com/kasunvimarshana/kv-saas-crm-erp.git
cd kv-saas-crm-erp

# Start Docker containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

## 🎯 Common Development Tasks

### Creating a New Module

```bash
# Create module structure
php artisan module:make NewModule

# The module will be created in: Modules/NewModule/
```

**Module Structure:**
```
Modules/NewModule/
├── Config/              # Module configuration
├── Database/            # Migrations, seeders, factories
├── Entities/            # Eloquent models
├── Http/
│   ├── Controllers/     # API controllers
│   ├── Requests/        # Form validations
│   └── Resources/       # API responses
├── Providers/           # Service providers
├── Repositories/        # Repository pattern
├── Routes/              # API/web routes
├── Services/            # Business logic
├── Tests/               # Unit & feature tests
└── module.json          # Module manifest
```

### Creating a Controller

**GitHub Copilot will automatically apply** `api-controllers.instructions.md` when you create a controller.

```bash
# Create controller
php artisan make:controller Modules/Sales/Http/Controllers/CustomerController
```

**Expected structure (Copilot will guide you):**
```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Sales\Http\Requests\CreateCustomerRequest;
use Modules\Sales\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    public function store(CreateCustomerRequest $request)
    {
        $customer = $this->customerService->createCustomer($request->validated());
        return new CustomerResource($customer);
    }
}
```

### Creating a Repository

**Copilot will apply** `repository-pattern.instructions.md` automatically.

```bash
# Create repository interface and implementation
# Interface: Modules/Sales/Repositories/Contracts/CustomerRepositoryInterface.php
# Implementation: Modules/Sales/Repositories/CustomerRepository.php
```

### Creating a Service

**Copilot will apply** `service-layer.instructions.md` automatically.

```bash
# Create service
# Location: Modules/Sales/Services/CustomerService.php
```

### Creating a Form Request

**Copilot will apply** `form-requests.instructions.md` automatically.

```bash
php artisan make:request Modules/Sales/Http/Requests/CreateCustomerRequest
```

### Creating a Migration

**Copilot will apply** `migrations.instructions.md` automatically.

```bash
php artisan make:migration create_customers_table --path=Modules/Sales/Database/Migrations
```

### Creating a Vue Component

**Copilot will apply** `vue-components.instructions.md` automatically.

Create file: `resources/js/components/CustomerForm.vue`

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'

// Copilot will guide you with Composition API pattern
</script>
```

## ⚡ Key Principles (Always Follow)

### 1. Native Implementation First
```
❌ DON'T: Install third-party packages
✅ DO: Use native Laravel/Vue features
```

**Examples:**
- ❌ `spatie/laravel-permission` → ✅ Native Gates & Policies
- ❌ `spatie/laravel-translatable` → ✅ JSON columns + `Translatable` trait
- ❌ `stancl/tenancy` → ✅ Native global scopes + `Tenantable` trait

### 2. Clean Architecture
```
Controller → Service → Repository → Entity
```

**Never:**
- ❌ Put business logic in controllers
- ❌ Use Eloquent directly in controllers
- ❌ Validate in controllers (use Form Requests)

### 3. Testing is Required
```bash
# Run tests before committing
./vendor/bin/pint                    # Format code
php artisan test                     # Run all tests
php artisan test --testsuite=Sales   # Run module tests
```

**Coverage target:** 80%+

## 🔍 Using GitHub Copilot Effectively

### Ask the Right Questions

**Good prompts:**
- "Create a CustomerService following the repository pattern"
- "Add a multi-tenant scope to this model"
- "Create a form request for customer creation with validation"
- "Write a feature test for the customer creation endpoint"

**Bad prompts:**
- "Add spatie/laravel-permission" (We use native implementations!)
- "Install a package for multi-language support" (We have native `Translatable` trait!)

### Let Copilot Guide You

When you create a file in a pattern-specific location, Copilot automatically applies the relevant instructions:

1. **Controller** → API controller patterns
2. **Service** → Service layer patterns
3. **Repository** → Repository pattern
4. **Form Request** → Validation patterns
5. **Migration** → Database schema patterns
6. **Test** → Testing patterns
7. **Vue Component** → Vue 3 Composition API patterns
8. **Event/Listener** → Event-driven patterns

## 🧪 Testing Your Changes

### Before Committing

```bash
# 1. Format code
./vendor/bin/pint

# 2. Clear caches
php artisan config:clear
php artisan cache:clear

# 3. Run tests
php artisan test

# 4. Check for errors
php artisan route:list
php artisan migrate:status
```

### Test Coverage

```bash
# Run with coverage report
php artisan test --coverage

# Minimum coverage: 80%
```

## 🚨 Common Pitfalls

### ❌ Installing Third-Party Packages

**Wrong:**
```bash
composer require spatie/laravel-permission
```

**Right:**
```php
// Use native Laravel Gates & Policies
Gate::define('edit-customer', function ($user, $customer) {
    return $user->id === $customer->user_id;
});
```

### ❌ Fat Controllers

**Wrong:**
```php
public function store(Request $request) {
    $validated = $request->validate([...]);
    $customer = Customer::create($validated);
    // ... lots of business logic
}
```

**Right:**
```php
public function store(CreateCustomerRequest $request) {
    $customer = $this->customerService->createCustomer($request->validated());
    return new CustomerResource($customer);
}
```

### ❌ Direct Eloquent in Controllers

**Wrong:**
```php
$customers = Customer::where('status', 'active')->get();
```

**Right:**
```php
$customers = $this->customerRepository->findActive();
```

## 📚 Additional Resources

### Documentation
- **[ARCHITECTURE.md](../ARCHITECTURE.md)** - System architecture
- **[DOMAIN_MODELS.md](../DOMAIN_MODELS.md)** - Entity models
- **[NATIVE_FEATURES.md](../NATIVE_FEATURES.md)** - Native implementations
- **[MODULE_DEVELOPMENT_GUIDE.md](../MODULE_DEVELOPMENT_GUIDE.md)** - Module guide

### Pattern-Specific Guides
- **[API Controllers](instructions/api-controllers.instructions.md)**
- **[Service Layer](instructions/service-layer.instructions.md)**
- **[Repository Pattern](instructions/repository-pattern.instructions.md)**
- **[Form Requests](instructions/form-requests.instructions.md)**
- **[Migrations](instructions/migrations.instructions.md)**
- **[Vue Components](instructions/vue-components.instructions.md)**
- **[Event-Driven](instructions/event-driven.instructions.md)**
- **[Module Tests](instructions/module-tests.instructions.md)**

## 🆘 Getting Help

1. **Check the pattern-specific instructions** - Automatically applied by Copilot
2. **Review the main instructions** - `.github/copilot-instructions.md`
3. **Search the documentation** - `DOCUMENTATION_INDEX.md` has everything
4. **Look at existing modules** - `Modules/Sales/` is a good reference
5. **Run validation commands** - `php artisan test`, `./vendor/bin/pint`

## ✅ Checklist for New Developers

- [ ] Read `.github/copilot-instructions.md` (main guidelines)
- [ ] Read `NATIVE_FEATURES.md` (no third-party packages!)
- [ ] Review `MODULE_DEVELOPMENT_GUIDE.md` (how to create modules)
- [ ] Set up Docker environment (`docker-compose up -d`)
- [ ] Run tests to verify setup (`php artisan test`)
- [ ] Format code with Pint (`./vendor/bin/pint`)
- [ ] Create a test module to practice
- [ ] Understand the architecture (Clean Architecture + DDD)
- [ ] Know the key principles (native implementations, repository pattern, service layer)

---

**Ready to start coding?** GitHub Copilot will guide you through the patterns! 🚀
