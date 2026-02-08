# GitHub Copilot Instructions

## Project Overview

This is **kv-saas-crm-erp** - a dynamic, enterprise-grade SaaS ERP/CRM system with a modular, maintainable architecture. The system is designed for global scalability with comprehensive multi-tenant, multi-organization, multi-currency, multi-language, and multi-location support.

**Core Mission**: Provide a fully-featured ERP/CRM platform that scales globally while maintaining code quality through Clean Architecture principles and Domain-Driven Design patterns.

**Key Modules**: Sales & CRM, Inventory Management, Warehouse Management, Accounting & Finance, Procurement, Human Resources.

## Tech Stack

### Backend
- **Framework**: Laravel 11.x
- **PHP Version**: 8.2+
- **Architecture**: Modular architecture using nWidart/laravel-modules
- **Database**: PostgreSQL (primary), Redis (cache)
- **Multi-tenancy**: stancl/tenancy v4.0+

### Key Dependencies
- **Authentication**: Laravel Sanctum 4.0+
- **Authorization**: spatie/laravel-permission 6.0+
- **Translations**: spatie/laravel-translatable 6.0+
- **Activity Logging**: spatie/laravel-activitylog 4.0+
- **API Queries**: spatie/laravel-query-builder 6.0+
- **Image Processing**: intervention/image 3.0+
- **File Storage**: league/flysystem-aws-s3-v3 3.0+
- **API Documentation**: darkaonline/l5-swagger 8.5+
- **Redis Client**: predis/predis 2.2+

### Testing & Quality
- **Testing**: PHPUnit 11.0+
- **Code Style**: Laravel Pint 1.13+
- **Mock Framework**: Mockery 1.6+
- **Error Handling**: spatie/laravel-ignition 2.4+

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

## Module Structure

All modules follow the nWidart/laravel-modules structure:

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
- Use **queued jobs** for long-running operations
- Use **events and listeners** for cross-module communication
- Use **policies** for authorization logic

### Multi-Tenancy Guidelines
- **ALWAYS** ensure tenant isolation in queries
- Use tenant-aware models from stancl/tenancy
- Never query across tenant boundaries
- Use central database for tenant metadata
- Use tenant-specific databases or schemas for tenant data
- Validate tenant context in all requests

### API Development
- Follow **RESTful** conventions
- Use proper HTTP verbs: GET, POST, PUT, PATCH, DELETE
- Use **API versioning** in routes (e.g., `/api/v1/orders`)
- Return consistent JSON responses using API resources
- Implement proper error handling with meaningful messages
- Document APIs using **OpenAPI 3.1** (Swagger) annotations
- Use **spatie/laravel-query-builder** for filtering, sorting, and including relationships

### Database & Models
- Use **migrations** for all schema changes (never modify database directly)
- Use **seeders** for test data
- Use **factories** for generating test data
- Always add **indexes** for foreign keys and frequently queried columns
- Use **soft deletes** where appropriate
- Implement **UUID or ULID** for primary keys in multi-tenant contexts
- Use **polymorphic relationships** for flexible associations
- Implement **translatable models** using spatie/laravel-translatable

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
- Use **Swagger/OpenAPI annotations** on controllers
- Generate API documentation: `php artisan l5-swagger:generate`
- Keep API documentation in sync with implementation

### Architecture Documentation
- Reference existing architecture docs: `ARCHITECTURE.md`, `DOMAIN_MODELS.md`
- Document architectural decisions in `ARCHITECTURE.md`
- Update module documentation when adding new modules

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

## Multi-Language Support

- Use `spatie/laravel-translatable` for model translations
- Store UI translations in `lang/` directory
- Use `trans()` or `__()` helpers for UI strings
- Support RTL languages where applicable
- Store translatable content in JSON columns

## Version Control

- Use **feature branches** for development
- Write **descriptive commit messages**
- Keep commits **atomic** and focused
- Reference issue numbers in commits
- Squash commits before merging when appropriate

## References

- [ARCHITECTURE.md](../ARCHITECTURE.md) - Complete architecture documentation
- [DOMAIN_MODELS.md](../DOMAIN_MODELS.md) - Domain model specifications
- [IMPLEMENTATION_ROADMAP.md](../IMPLEMENTATION_ROADMAP.md) - Development phases
- [MODULE_DEVELOPMENT_GUIDE.md](../MODULE_DEVELOPMENT_GUIDE.md) - Module development guide
- [DOCUMENTATION_INDEX.md](../DOCUMENTATION_INDEX.md) - Complete documentation index

---

**Remember**: Always prioritize code quality, security, and maintainability. When in doubt, follow Clean Architecture and SOLID principles.
