---
applyTo:
  - "**/*.php"
  - "**/*.vue"
  - "**/*.js"
  - "**/*.ts"
  - "**/composer.json"
  - "**/package.json"
  - "**/*.md"
---

# GitHub Copilot Instructions

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


## Project Overview

This is **kv-saas-crm-erp** - a dynamic, enterprise-grade SaaS ERP/CRM system with a modular, maintainable architecture. The system is designed for global scalability with comprehensive multi-tenant, multi-organization, multi-currency, multi-language, and multi-location support.

**Core Mission**: Provide a fully-featured ERP/CRM platform that scales globally while maintaining code quality through Clean Architecture principles and Domain-Driven Design patterns.

**Key Modules**: Sales & CRM, Inventory Management, Warehouse Management, Accounting & Finance, Procurement, Human Resources.

## Tech Stack

### Backend
- **Framework**: Laravel 11.x (Native features only)
- **PHP Version**: 8.2+
- **Architecture**: Modular architecture using Service Provider pattern (native Laravel)
- **Database**: PostgreSQL (primary), Redis (cache/queue)
- **Multi-tenancy**: Native implementation using global scopes and middleware
- **Authentication**: Laravel Sanctum (native)
- **File Storage**: Laravel Storage facade with Flysystem (included in Laravel)

### Native Implementations (NO Third-Party Packages)
See [NATIVE_FEATURES.md](../NATIVE_FEATURES.md) for complete details:
- **Multi-Language**: Native JSON column-based translations (`Translatable` trait)
- **Multi-Tenant**: Native global scope-based tenant isolation (`Tenantable` trait)
- **Authorization**: Native Gates and Policies with JSON permission storage (`HasPermissions` trait)
- **Activity Logging**: Native Eloquent event-based audit trail (`LogsActivity` trait)
- **API Query Builder**: Native request parameter parsing for filters/sorts (`QueryBuilder` class)
- **Image Processing**: Native PHP GD/Imagick extension usage
- **Repository Pattern**: Native interface-based data access abstraction
- **Module System**: Native Laravel Service Provider-based modules

### Testing & Quality
- **Testing**: PHPUnit 11.0+ (native Laravel testing)
- **Code Style**: Laravel Pint 1.13+
- **Mock Framework**: Mockery 1.6+ (included with Laravel)
- **Factories & Seeders**: Native Laravel factory system

### Frontend
- **Framework**: Vue.js 3 (Composition API, native features only)
- **Build Tool**: Vite (included with Laravel)
- **Styling**: Tailwind CSS (utility-first, minimal bundle)
- **State Management**: Vue 3 Composition API (native, no Vuex/Pinia required)
- **HTTP Client**: Native Fetch API or Axios (minimal abstraction)
- **Routing**: Vue Router (for SPA features)
- **Form Validation**: Native HTML5 + custom Vue composables
- **UI Components**: Custom components (NO component libraries like Vuetify, Element, etc.)

### Frontend Guidelines
- **NO third-party component libraries** - Build custom, reusable components
- Use **Composition API** for logic reusability via composables
- Use **Provide/Inject** for dependency injection (avoid prop drilling)
- Use **Teleport** for modals and overlays (native Vue 3 feature)
- Use **Suspense** for async component loading (native Vue 3 feature)
- Implement **custom directives** for DOM manipulation needs
- Use **TypeScript** for type safety (optional but recommended)
- Follow **Vue 3 Style Guide** and best practices

## Boundaries and Exclusions

### ⛔ Never Modify These Directories/Files
- **`vendor/`** - Composer dependencies (auto-managed)
- **`node_modules/`** - NPM dependencies (auto-managed)
- **`storage/`** - Runtime storage (logs, cache, uploads)
- **`bootstrap/cache/`** - Bootstrap cache files
- **`.env`** - Environment configuration (NEVER commit)
- **`.env.example`** - Can be updated for new config keys only

### 🔒 Protected Files (Modify with Extreme Care)
- **`composer.json`** - Only add dependencies after security review
- **`package.json`** - Only add dependencies after security review
- **`config/*.php`** - Configuration files (requires review)
- **`docker-compose.yml`** - Infrastructure (requires review)
- **`phpunit.xml`** - Test configuration (requires review)

### 🚫 Security Rules
- **NEVER** hardcode credentials, API keys, or secrets
- **NEVER** commit files containing sensitive data
- **NEVER** disable security features (CSRF, XSS protection)
- **NEVER** bypass authentication or authorization checks
- **ALWAYS** validate and sanitize user input
- **ALWAYS** use parameterized queries (never raw SQL with concatenation)
- **ALWAYS** use HTTPS in production
- **ALWAYS** follow the principle of least privilege

### 📝 What You CAN Modify
- **`Modules/`** - All module code (following architectural patterns)
- **`app/`** - Application core code
- **`routes/`** - Route definitions
- **`resources/`** - Frontend assets and views
- **`tests/`** - Test suites
- **`database/migrations/`** - Database schema (create new migrations only)
- **`database/seeders/`** - Database seeders
- **`database/factories/`** - Model factories
- Documentation files (`.md`)

## Build, Test & Validation Commands

**IMPORTANT**: Always run these commands to validate your changes before finalizing a pull request.

### Setup & Dependencies
```bash
# Install PHP dependencies
composer install

# Install and update dependencies
composer update

# Generate application key (if needed)
php artisan key:generate

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Code Style & Formatting
```bash
# Format code using Laravel Pint (REQUIRED before commit)
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test
```

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Core
php artisan test --testsuite=Sales
php artisan test --testsuite=Tenancy

# Run tests with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Unit/ExampleTest.php

# Run tests in parallel (faster)
php artisan test --parallel
```

### Database Operations
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Refresh database (drops all tables and re-runs migrations)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Refresh database with seeding
php artisan migrate:fresh --seed
```

### Module-Specific Commands
```bash
# List all modules
php artisan module:list

# Enable a module
php artisan module:enable ModuleName

# Disable a module
php artisan module:disable ModuleName

# Create a new module
php artisan module:make ModuleName

# Run module migrations
php artisan module:migrate ModuleName

# Run module seeder
php artisan module:seed ModuleName
```

### Frontend Build Commands
```bash
# Install frontend dependencies
npm install

# Run development server with hot reload
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type check (if using TypeScript)
npm run type-check

# Lint Vue files (optional)
npm run lint
```

### API Documentation
```bash
# View OpenAPI specification (manually maintained)
cat docs/api/openapi.yaml

# Serve API documentation locally
php artisan serve
# Then visit: http://localhost:8000/docs
```

### Validation Workflow
Before finalizing any pull request, run this validation sequence:

```bash
# 1. Format backend code
./vendor/bin/pint

# 2. Clear caches
php artisan config:clear && php artisan cache:clear

# 3. Run all backend tests
php artisan test

# 4. Build frontend assets
npm run build

# 5. Validate OpenAPI spec (if API changes were made)
php artisan l5-swagger:generate
```

### Docker-Based Development
If using Docker (recommended for consistency):

```bash
# Start containers
docker-compose up -d

# Run commands inside container
docker-compose exec app composer install
docker-compose exec app php artisan test
docker-compose exec app ./vendor/bin/pint

# Stop containers
docker-compose down
```

## Project Structure

Understanding the directory structure helps navigate the codebase efficiently:

```
├── .github/                    # GitHub configuration
│   └── copilot-instructions.md # This file
├── Modules/                    # All business modules
│   ├── Core/                   # Core shared functionality
│   ├── Sales/                  # Sales & CRM module
│   ├── Tenancy/                # Multi-tenancy module
│   └── {ModuleName}/          # Each module follows same structure:
│       ├── Config/             # Module configuration
│       ├── Database/           # Migrations, seeders, factories
│       ├── Entities/           # Eloquent models (domain entities)
│       ├── Http/               # Controllers, requests, resources
│       ├── Providers/          # Service providers
│       ├── Repositories/       # Repository implementations
│       ├── Routes/             # API and web routes
│       ├── Services/           # Application services
│       ├── Tests/              # Module-specific tests
│       └── module.json         # Module manifest
├── app/                        # Laravel application core
├── bootstrap/                  # Laravel bootstrap
├── config/                     # Application configuration
├── database/                   # Global migrations/seeders
├── public/                     # Public assets
├── resources/                  # Views, frontend assets
├── routes/                     # Global routes
├── storage/                    # File storage, logs, cache
├── tests/                      # Application-level tests
├── composer.json              # PHP dependencies
├── phpunit.xml                # PHPUnit configuration
└── docker-compose.yml         # Docker configuration
```

## Architectural Principles

### 1. Clean Architecture
- **Core Principle**: All dependencies point inward toward business logic
- **Layers** (from outer to inner):
  1. External Interfaces & Frameworks (UI, Database, APIs)
  2. Interface Adapters (Controllers, Presenters, Gateways)
  3. Application Business Rules (Use Cases, Application Services)
  4. Enterprise Business Rules (Entities, Domain Services, Aggregates)
- **Rule**: Core business logic NEVER depends on infrastructure
- **Rule**: Infrastructure depends on abstractions defined by core

### 2. SOLID Principles
- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification (use plugin architecture)
- **Liskov Substitution**: Use interfaces for substitutable implementations
- **Interface Segregation**: Create small, focused interfaces
- **Dependency Inversion**: Depend on abstractions, not concretions

### 3. Domain-Driven Design (DDD)
- Use rich domain models aligned with business logic
- Implement aggregates to maintain consistency boundaries
- Use domain events for cross-module communication
- Repository pattern for data access abstraction
- Value objects for domain concepts without identity

### 4. Hexagonal Architecture (Ports & Adapters)
- Core business logic isolated from external concerns
- Define ports (interfaces) in the core
- Implement adapters for external integrations
- Primary adapters: REST API, GraphQL, gRPC, Web UI, CLI
- Secondary adapters: Database, Message Queue, File System, Cache, External APIs

### 5. Native Implementation First
**CRITICAL**: Always prioritize native Laravel/Vue features over third-party packages.

**Decision Process**:
1. **Check Native Features**: Can this be done with Laravel/Vue built-in features?
2. **Review NATIVE_FEATURES.md**: Does a native implementation already exist?
3. **Consider Building Custom**: Is the functionality simple enough to implement?
4. **Evaluate Package Need**: Only use packages if:
   - Feature is complex and well-tested in the package
   - Package is LTS (Long Term Support) maintained
   - Package is from Laravel/Vue core teams
   - No suitable native alternative exists

**Examples of Native Implementations**:
- ✅ Multi-language: Use JSON columns + `Translatable` trait (NO spatie/laravel-translatable)
- ✅ Multi-tenant: Use global scopes + `Tenantable` trait (NO stancl/tenancy)
- ✅ RBAC: Use Gates/Policies + `HasPermissions` trait (NO spatie/laravel-permission)
- ✅ Activity Logs: Use Eloquent events + `LogsActivity` trait (NO spatie/laravel-activitylog)
- ✅ Image Processing: Use PHP GD/Imagick extensions (NO intervention/image)
- ✅ API Filtering: Custom QueryBuilder class (NO spatie/laravel-query-builder)
- ✅ File Upload: Laravel Storage facade (included, NO additional packages)
- ✅ Queue Jobs: Laravel Queue (native Redis/Database driver)
- ✅ Email: Laravel Mail facade with native drivers
- ✅ PDF Generation: Native DomPDF (if needed) or HTML to PDF
- ✅ Excel: Native CSV generation or PhpSpreadsheet (if absolutely needed)

**Benefits of Native Approach**:
- 🎯 Complete control and understanding of all code
- 🚀 29% performance improvement (fewer classes, less overhead)
- 🔒 Zero supply chain security risks
- 📦 No abandoned package risks
- 🧪 Easier testing and debugging
- 📚 Better team knowledge and ownership
- ⚡ Faster deployment (fewer dependencies)

See [NATIVE_FEATURES.md](../NATIVE_FEATURES.md) for complete implementation guide.

## Module Structure

All modules follow the native Laravel Service Provider-based structure:

```
Modules/
  {ModuleName}/
    Config/          # Module configuration
    Database/        # Migrations, seeders, factories
    Entities/        # Eloquent models (Domain entities)
    Http/
      Controllers/   # API and web controllers
      Requests/      # Form request validation
      Resources/     # API resources (transformers)
    Providers/       # Service providers
    Repositories/    # Repository pattern implementations
    Routes/          # API and web routes
    Services/        # Application services and use cases
    Tests/           # Module-specific tests
    Traits/          # Reusable traits
    module.json      # Module manifest (Odoo-inspired)
```

### Module Guidelines
- Each module is self-contained and independently deployable
- Modules communicate via events and domain services
- Never create direct dependencies between modules
- Use the Core module for shared functionality
- Follow the manifest system in module.json for dependencies

## Coding Guidelines

### PHP Style & Conventions
- **PSR-12** coding standard
- **Laravel coding style** as enforced by Laravel Pint
- Use **type hints** for all parameters and return types
- Use **strict types**: Add `declare(strict_types=1);` at the top of each file
- **Naming conventions**:
  - Classes: PascalCase (e.g., `SalesOrderService`)
  - Methods: camelCase (e.g., `createOrder()`)
  - Variables: camelCase (e.g., `$orderTotal`)
  - Constants: UPPER_SNAKE_CASE (e.g., `MAX_RETRY_ATTEMPTS`)
  - Database tables: snake_case, plural (e.g., `sales_orders`)
  - Database columns: snake_case (e.g., `order_total`)

### Laravel Best Practices
- Use **service containers** for dependency injection
- Use **repository pattern** for data access, not direct Eloquent in controllers
- Use **form requests** for validation
- Use **resources** for API responses
- Use **eloquent relationships** properly (eager loading to avoid N+1)
- Use **database transactions** for operations affecting multiple tables
- Use **queued jobs** for long-running operations (native Laravel Queue)
- Use **events and listeners** for cross-module communication
- Use **policies** for authorization logic (native Laravel Gates & Policies)

### Vue.js Best Practices
- Use **Composition API** for all components (no Options API)
- Use **composables** for reusable logic (e.g., `useAuth`, `useApi`, `useForm`)
- Use **TypeScript** for type safety (recommended)
- Use **props validation** with PropTypes or TypeScript interfaces
- Use **emit events** for child-to-parent communication
- Use **provide/inject** for dependency injection across component tree
- Use **computed properties** for derived state (reactive)
- Use **watch** sparingly, prefer computed or methods
- Use **async/await** for asynchronous operations
- Use **Suspense** for async component loading (Vue 3 native)
- Use **Teleport** for modals, tooltips, and overlays (Vue 3 native)
- **NO component libraries**: Build custom, reusable components
- **NO state management libraries**: Use Composition API + composables

### Vue.js Component Structure
```vue
<script setup lang="ts">
// 1. Imports
import { ref, computed, onMounted } from 'vue'
import type { Customer } from '@/types'

// 2. Props & Emits
interface Props {
  customer: Customer
  readonly?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  readonly: false
})
const emit = defineEmits<{
  update: [customer: Customer]
  delete: [id: string]
}>()

// 3. Reactive State
const isLoading = ref(false)
const errors = ref<string[]>([])

// 4. Computed Properties
const displayName = computed(() => {
  return `${props.customer.firstName} ${props.customer.lastName}`
})

// 5. Methods
const handleSubmit = async () => {
  isLoading.value = true
  try {
    // API call
    emit('update', props.customer)
  } catch (error) {
    errors.value.push(error.message)
  } finally {
    isLoading.value = false
  }
}

// 6. Lifecycle Hooks
onMounted(() => {
  // Initialize component
})
</script>

<template>
  <!-- Template code -->
</template>

<style scoped>
/* Component-specific styles */
</style>
```

### Multi-Tenancy Guidelines
- **ALWAYS** ensure tenant isolation in queries
- Use native `Tenantable` trait (NOT stancl/tenancy)
- Never query across tenant boundaries
- Use central database for tenant metadata
- Use tenant-specific databases or schemas for tenant data
- Validate tenant context in all requests
- Add tenant_id to all tenant-scoped tables
- Use global scopes for automatic tenant filtering

### API Development
- Follow **RESTful** conventions
- Use proper HTTP verbs: GET, POST, PUT, PATCH, DELETE
- Use **API versioning** in routes (e.g., `/api/v1/orders`)
- Return consistent JSON responses using API resources
- Implement proper error handling with meaningful messages
- Document APIs using **OpenAPI 3.1** specification (manual YAML files)
- Use native **QueryBuilder** class for filtering, sorting, and including relationships (NO spatie package)
- Include **pagination** metadata in responses
- Support **field selection** via query parameters (e.g., `?fields=id,name,email`)

### Database & Models
- Use **migrations** for all schema changes (never modify database directly)
- Use **seeders** for test data
- Use **factories** for generating test data
- Always add **indexes** for foreign keys and frequently queried columns
- Use **soft deletes** where appropriate
- Implement **UUID or ULID** for primary keys in multi-tenant contexts
- Use **polymorphic relationships** for flexible associations
- Implement **translatable models** using native `Translatable` trait (NO spatie package)
- Use **JSON columns** for flexible data (translations, metadata, custom fields)
- Use **Eloquent observers** for model event handling

### Security Best Practices
- **NEVER** hardcode credentials or secrets
- Use `.env` for configuration, never commit `.env` files
- **Always** validate and sanitize user input
- Use **parameterized queries** (Eloquent/Query Builder, never raw concatenation)
- Implement **CSRF protection** for web routes
- Use **rate limiting** on API routes
- Implement **proper authentication** (Sanctum tokens)
- Implement **proper authorization** (policies and gates)
- Log security-relevant events using spatie/laravel-activitylog
- Set secure cookie options: `{ httpOnly: true, secure: true, sameSite: 'strict' }`
- Validate file uploads (type, size, content)
- Use **HTTPS** in production

### Error Handling
- Use **try-catch** blocks for operations that may fail
- Log errors appropriately (use Laravel's Log facade)
- Return user-friendly error messages (don't expose stack traces to users)
- Use **custom exception classes** for domain-specific errors
- Implement **global exception handler** for consistent error responses

### Performance
- Use **caching** strategically (Redis for session, cache, queues)
- Implement **database query optimization** (use indexes, avoid N+1)
- Use **eager loading** for relationships
- Use **chunk** for processing large datasets
- Implement **queue workers** for background jobs
- Use **Laravel Horizon** for queue monitoring (if installed)
- Profile and optimize slow queries

## Testing Requirements

### General Testing Guidelines
- Target **80%+ code coverage**
- Write tests for all new features and bug fixes
- Follow **AAA pattern**: Arrange, Act, Assert
- Use **descriptive test names**: `test_it_creates_order_with_valid_data()`
- Use **factories** for test data generation
- Use **database transactions** in tests for cleanup

### Test Types
1. **Unit Tests**: Test individual classes/methods in isolation
   - Mock external dependencies
   - Fast execution
   - Location: `Modules/{Module}/Tests/Unit/`

2. **Feature Tests**: Test HTTP endpoints and user workflows
   - Test complete request/response cycles
   - Test authentication and authorization
   - Location: `Modules/{Module}/Tests/Feature/`

3. **Integration Tests**: Test module interactions
   - Test event listeners and handlers
   - Test cross-module workflows
   - Location: `tests/Integration/`

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific module tests
php artisan test --testsuite=Sales

# Run with coverage
php artisan test --coverage
```

## Documentation

### Code Documentation
- Document **all public methods** with PHPDoc comments
- Include parameter types, return types, and exceptions thrown
- Add descriptions for complex logic
- Keep documentation up-to-date with code changes

### API Documentation
- Maintain **OpenAPI 3.1 YAML** specification files in `docs/api/`
- Use manual YAML files (NO auto-generation packages)
- Keep API documentation in sync with implementation
- Organize specs: `openapi.yaml` (main), `paths/`, `components/`
- Serve documentation via custom route: `/docs`

### Architecture Documentation
- **Primary References**:
  - [ARCHITECTURE.md](../ARCHITECTURE.md) - Complete architecture patterns
  - [DOMAIN_MODELS.md](../DOMAIN_MODELS.md) - Entity specifications
  - [NATIVE_FEATURES.md](../NATIVE_FEATURES.md) - Native implementations guide
  - [MODULE_DEVELOPMENT_GUIDE.md](../MODULE_DEVELOPMENT_GUIDE.md) - Module development
  - [DOCUMENTATION_INDEX.md](../DOCUMENTATION_INDEX.md) - Complete documentation index
- Document architectural decisions in `ARCHITECTURE.md`
- Update module documentation when adding new modules
- Reference patterns from comprehensive documentation

## Common Patterns & Examples

### Repository Pattern
```php
// Interface
interface SalesOrderRepositoryInterface {
    public function create(array $data): SalesOrder;
    public function findById(string $id): ?SalesOrder;
}

// Implementation
class EloquentSalesOrderRepository implements SalesOrderRepositoryInterface {
    public function create(array $data): SalesOrder {
        return SalesOrder::create($data);
    }
}
```

### Service Layer
```php
class CreateSalesOrderService {
    public function __construct(
        private SalesOrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository
    ) {}
    
    public function execute(CreateSalesOrderRequest $request): SalesOrder {
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->create($request->validated());
            // Additional business logic
            DB::commit();
            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### API Resource
```php
class SalesOrderResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total' => $this->total,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Event-Driven Communication
```php
// Event
class OrderCreated {
    public function __construct(public SalesOrder $order) {}
}

// Listener
class SendOrderConfirmationEmail {
    public function handle(OrderCreated $event): void {
        // Send email logic
    }
}
```

### Vue.js Composable Pattern
```typescript
// composables/useCustomers.ts
import { ref, computed } from 'vue'
import type { Customer } from '@/types'

export function useCustomers() {
  const customers = ref<Customer[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const activeCustomers = computed(() => 
    customers.value.filter(c => c.status === 'active')
  )

  const fetchCustomers = async () => {
    isLoading.value = true
    error.value = null
    try {
      const response = await fetch('/api/v1/customers')
      customers.value = await response.json()
    } catch (e) {
      error.value = e.message
    } finally {
      isLoading.value = false
    }
  }

  return {
    customers,
    activeCustomers,
    isLoading,
    error,
    fetchCustomers
  }
}
```

### Vue.js Component with Composable
```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { useCustomers } from '@/composables/useCustomers'

const { customers, activeCustomers, isLoading, fetchCustomers } = useCustomers()

onMounted(() => {
  fetchCustomers()
})
</script>

<template>
  <div class="customers-list">
    <div v-if="isLoading">Loading...</div>
    <div v-else>
      <div v-for="customer in activeCustomers" :key="customer.id">
        {{ customer.name }}
      </div>
    </div>
  </div>
</template>
```

## Multi-Language Support

- Use native `Translatable` trait for model translations (NO spatie package)
- Store translations in JSON columns: `{"en":"Name","es":"Nombre","fr":"Nom"}`
- Store UI translations in `lang/` directory (native Laravel translations)
- Use `trans()` or `__()` helpers for UI strings
- Use Vue i18n composable for frontend translations (custom implementation)
- Support RTL languages where applicable
- Implement language switcher in UI
- Set locale based on user preferences or browser settings

## Version Control

- Use **feature branches** for development
- Write **descriptive commit messages**
- Keep commits **atomic** and focused
- Reference issue numbers in commits
- Squash commits before merging when appropriate

## References

### Core Documentation
- [ARCHITECTURE.md](../ARCHITECTURE.md) - Complete architecture documentation
- [DOMAIN_MODELS.md](../DOMAIN_MODELS.md) - Domain model specifications
- [NATIVE_FEATURES.md](../NATIVE_FEATURES.md) - **Native implementations (MUST READ)**
- [MODULE_DEVELOPMENT_GUIDE.md](../MODULE_DEVELOPMENT_GUIDE.md) - Module development guide
- [DOCUMENTATION_INDEX.md](../DOCUMENTATION_INDEX.md) - Complete documentation index

### Implementation Guides
- [IMPLEMENTATION_ROADMAP.md](../IMPLEMENTATION_ROADMAP.md) - Development phases
- [LARAVEL_IMPLEMENTATION_TEMPLATES.md](../LARAVEL_IMPLEMENTATION_TEMPLATES.md) - Code templates
- [INTEGRATION_GUIDE.md](../INTEGRATION_GUIDE.md) - System integration patterns
- [NATIVE_IMPLEMENTATION_GUIDE.md](../NATIVE_IMPLEMENTATION_GUIDE.md) - Native approach philosophy

### Additional Resources
- [RESOURCE_ANALYSIS.md](../RESOURCE_ANALYSIS.md) - Analysis of 15+ industry resources
- [CONCEPTS_REFERENCE.md](../CONCEPTS_REFERENCE.md) - Pattern encyclopedia
- [openapi-template.yaml](../openapi-template.yaml) - API specification template

---

## Pattern-Specific Instructions

This repository uses specialized instruction files for specific code patterns. These files provide detailed guidelines when working with particular file types:

<a href="instructions/api-controllers.instructions.md">API Controllers</a> - Apply to: `**/Modules/**/Http/Controllers/**/*.php`
<a href="instructions/event-driven.instructions.md">Event-Driven Architecture</a> - Apply to: `**/Events/**/*.php`, `**/Listeners/**/*.php`, `**/Observers/**/*.php`
<a href="instructions/form-requests.instructions.md">Form Request Validation</a> - Apply to: `**/Http/Requests/**/*.php`
<a href="instructions/migrations.instructions.md">Database Migrations</a> - Apply to: `**/Database/Migrations/**/*.php`
<a href="instructions/module-tests.instructions.md">Module Tests</a> - Apply to: `**/Modules/**/Tests/**/*.php`
<a href="instructions/repository-pattern.instructions.md">Repository Pattern</a> - Apply to: `**/Repositories/**/*.php`
<a href="instructions/service-layer.instructions.md">Service Layer</a> - Apply to: `**/Services/**/*.php`
<a href="instructions/vue-components.instructions.md">Vue.js Components</a> - Apply to: `**/*.vue`

These pattern-specific instructions are automatically applied when you work with files matching their patterns.

---

**Remember**: Always prioritize code quality, security, and maintainability. When in doubt, follow Clean Architecture and SOLID principles.
