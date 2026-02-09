# Repository Pattern Requirements

When implementing the Repository Pattern, follow these guidelines to maintain clean architecture and testability.

## Overview

The Repository Pattern provides an abstraction layer between the domain/business logic and data access layers. It enables:
- Testability through dependency injection
- Separation of concerns
- Consistent data access interface
- Easy switching between data sources

## Repository Structure

### 1. Define Repository Interface

Always start by defining an interface that describes the contract:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Sales\Entities\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Find customer by ID
     */
    public function findById(string $id): ?Customer;

    /**
     * Find customer by email address
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Get all customers
     */
    public function all(): Collection;

    /**
     * Get customers with pagination
     */
    public function paginate(int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;

    /**
     * Create a new customer
     */
    public function create(array $data): Customer;

    /**
     * Update an existing customer
     */
    public function update(Customer $customer, array $data): Customer;

    /**
     * Delete a customer
     */
    public function delete(Customer $customer): bool;

    /**
     * Find active customers
     */
    public function findActive(): Collection;

    /**
     * Find customers with outstanding balance
     */
    public function findWithOutstandingBalance(): Collection;

    /**
     * Search customers by name or email
     */
    public function search(string $query): Collection;
}
```

### 2. Implement Repository Class

Implement the interface using Eloquent:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Create a new repository instance
     */
    public function __construct(
        protected Customer $model
    ) {}

    /**
     * Find customer by ID
     */
    public function findById(string $id): ?Customer
    {
        return $this->model->find($id);
    }

    /**
     * Find customer by email address
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get all customers
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get customers with pagination
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new customer
     */
    public function create(array $data): Customer
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing customer
     */
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    /**
     * Delete a customer
     */
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    /**
     * Find active customers
     */
    public function findActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Find customers with outstanding balance
     */
    public function findWithOutstandingBalance(): Collection
    {
        return $this->model
            ->where('current_balance', '>', 0)
            ->orderBy('current_balance', 'desc')
            ->get();
    }

    /**
     * Search customers by name or email
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }
}
```

### 3. Register Repository in Service Provider

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Repositories\CustomerRepository;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load module resources
    }
}
```

## Using Repositories in Services

Always inject repository interfaces, not concrete implementations:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

class CustomerService
{
    /**
     * Create a new service instance
     */
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Create a new customer
     */
    public function createCustomer(array $data): Customer
    {
        DB::beginTransaction();
        try {
            $customer = $this->customerRepository->create($data);
            
            // Additional business logic here
            // - Send welcome email
            // - Create default settings
            // - Log activity
            
            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update customer information
     */
    public function updateCustomer(string $id, array $data): Customer
    {
        $customer = $this->customerRepository->findById($id);
        
        if (!$customer) {
            throw new \Exception("Customer not found: {$id}");
        }

        return $this->customerRepository->update($customer, $data);
    }

    /**
     * Get active customers
     */
    public function getActiveCustomers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->customerRepository->findActive();
    }
}
```

## Base Repository Pattern

Create a base repository for common CRUD operations:

```php
<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Create a new repository instance
     */
    public function __construct(
        protected Model $model
    ) {}

    /**
     * Find by ID
     */
    public function findById(string $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find by column value
     */
    public function findBy(string $column, mixed $value): ?Model
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    /**
     * Delete a record
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Find where conditions match
     */
    public function findWhere(array $conditions): Collection
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get();
    }

    /**
     * Find where with pagination
     */
    public function findWherePaginated(array $conditions, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->paginate($perPage);
    }
}
```

## Using Repositories in Controllers

Controllers should inject services, not repositories directly:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Sales\Http\Requests\CreateCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use Modules\Sales\Services\CustomerService;

class CustomerController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers
     */
    public function index(): JsonResponse
    {
        $customers = $this->customerService->getActiveCustomers();
        return response()->json(CustomerResource::collection($customers));
    }

    /**
     * Store a newly created customer
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());
        return response()->json(new CustomerResource($customer), 201);
    }

    /**
     * Update the specified customer
     */
    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($id, $request->validated());
        return response()->json(new CustomerResource($customer));
    }
}
```

## Testing Repositories

### 1. Unit Test with Mock Repository

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Services\CustomerService;

class CustomerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_customer_successfully(): void
    {
        // Arrange
        $mockRepository = Mockery::mock(CustomerRepositoryInterface::class);
        $service = new CustomerService($mockRepository);

        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890'
        ];

        $expectedCustomer = new Customer($customerData);
        $expectedCustomer->id = 'uuid-123';

        $mockRepository
            ->shouldReceive('create')
            ->once()
            ->with($customerData)
            ->andReturn($expectedCustomer);

        // Act
        $result = $service->createCustomer($customerData);

        // Assert
        $this->assertEquals('uuid-123', $result->id);
        $this->assertEquals('John Doe', $result->name);
    }
}
```

### 2. Integration Test with Real Repository

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Repositories\CustomerRepository;

class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_customer(): void
    {
        // Arrange
        $repository = new CustomerRepository(new Customer());
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890'
        ];

        // Act
        $customer = $repository->create($data);

        // Assert
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }

    public function test_it_finds_active_customers(): void
    {
        // Arrange
        Customer::factory()->count(3)->create(['status' => 'active']);
        Customer::factory()->count(2)->create(['status' => 'inactive']);

        $repository = new CustomerRepository(new Customer());

        // Act
        $activeCustomers = $repository->findActive();

        // Assert
        $this->assertCount(3, $activeCustomers);
        $activeCustomers->each(function ($customer) {
            $this->assertEquals('active', $customer->status);
        });
    }
}
```

## Advanced Repository Patterns

### 1. Criteria Pattern

```php
<?php

declare(strict_types=1);

namespace Modules\Core\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

interface CriteriaInterface
{
    /**
     * Apply criteria to query
     */
    public function apply(Builder $query): Builder;
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Repositories\Criteria\CriteriaInterface;

class ActiveCustomersCriteria implements CriteriaInterface
{
    public function apply(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
```

### 2. Repository with Criteria

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Core\Repositories\Criteria\CriteriaInterface;
use Modules\Sales\Entities\Customer;

class CustomerRepository extends BaseRepository
{
    protected array $criteria = [];

    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Add criteria to repository
     */
    public function withCriteria(CriteriaInterface $criteria): self
    {
        $this->criteria[] = $criteria;
        return $this;
    }

    /**
     * Apply all criteria and get results
     */
    public function get(): Collection
    {
        $query = $this->model->newQuery();

        foreach ($this->criteria as $criteria) {
            $query = $criteria->apply($query);
        }

        $this->criteria = []; // Reset criteria
        return $query->get();
    }
}
```

## Common Pitfalls to Avoid

1. **Don't put business logic in repositories** - Keep them focused on data access
2. **Don't inject repositories in controllers** - Use service layer
3. **Don't use concrete implementations** - Always inject interfaces
4. **Don't forget to register bindings** - Register in service provider
5. **Don't bypass repositories** - Always use them for data access
6. **Don't create too many methods** - Keep repository focused and cohesive
7. **Don't forget error handling** - Handle exceptions appropriately
8. **Don't mix concerns** - Keep repository methods single-purpose

## Best Practices Checklist

- [x] Define repository interface first
- [x] Implement interface with Eloquent
- [x] Register binding in service provider
- [x] Inject interface, not implementation
- [x] Use repositories in services, not controllers
- [x] Write unit tests with mocked repositories
- [x] Write integration tests with real repositories
- [x] Keep repository methods focused on data access
- [x] Use base repository for common CRUD operations
- [x] Document all repository methods
- [x] Use type hints and return types
- [x] Handle exceptions appropriately
