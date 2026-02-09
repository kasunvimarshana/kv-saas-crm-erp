---
applyTo: "**/Modules/**/Tests/**/*.php"
---

# Module Test Requirements

When writing tests for Laravel modules, follow these guidelines to ensure consistency and maintainability:

## Test Structure

1. **Use proper namespaces**: Tests should be in the `Modules\{ModuleName}\Tests` namespace
2. **Organize by type**:
   - `Unit/` - Unit tests for individual classes/methods
   - `Feature/` - Feature tests for HTTP endpoints and workflows
   - `Integration/` - Integration tests for module interactions

## Testing Standards

### 1. Use Descriptive Test Names
```php
// Good
public function test_it_creates_sales_order_with_valid_data()
public function test_it_returns_404_when_order_not_found()
public function test_it_validates_required_fields()

// Avoid
public function testCreate()
public function testValidation()
```

### 2. Follow AAA Pattern
```php
public function test_example()
{
    // Arrange - Set up test data and conditions
    $user = User::factory()->create();
    
    // Act - Perform the action being tested
    $response = $this->actingAs($user)->post('/api/orders', $data);
    
    // Assert - Verify the results
    $response->assertStatus(201);
    $this->assertDatabaseHas('orders', ['id' => $response->json('data.id')]);
}
```

### 3. Use Factories for Test Data
```php
// Always use factories instead of manual array creation
$customer = Customer::factory()->create();
$products = Product::factory()->count(3)->create();

// For specific attributes, override factory defaults
$order = Order::factory()->create(['status' => 'pending']);
```

### 4. Test Multi-Tenancy Isolation
```php
// Always verify tenant isolation in multi-tenant modules
public function test_user_cannot_access_other_tenant_data()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    tenancy()->initialize($tenant1);
    $order1 = Order::factory()->create();
    
    tenancy()->initialize($tenant2);
    $response = $this->get("/api/orders/{$order1->id}");
    
    $response->assertStatus(404);
}
```

### 5. Clean Up with Database Transactions
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase; // Automatically rolls back after each test
    
    // Your tests here
}
```

### 6. Mock External Dependencies
```php
// Mock external services and APIs
$mockService = Mockery::mock(ExternalService::class);
$mockService->shouldReceive('process')
    ->once()
    ->with($data)
    ->andReturn($expectedResult);

$this->app->instance(ExternalService::class, $mockService);
```

### 7. Test Authorization
```php
public function test_unauthorized_user_cannot_create_order()
{
    $response = $this->post('/api/orders', $data);
    $response->assertStatus(401);
}

public function test_user_without_permission_cannot_delete_order()
{
    $user = User::factory()->create(); // No permissions
    $order = Order::factory()->create();
    
    $response = $this->actingAs($user)->delete("/api/orders/{$order->id}");
    $response->assertStatus(403);
}
```

### 8. Test API Response Structure
```php
public function test_order_list_returns_correct_structure()
{
    Order::factory()->count(3)->create();
    
    $response = $this->get('/api/orders');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'order_number',
                    'total',
                    'created_at',
                    'updated_at'
                ]
            ],
            'meta' => ['current_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next']
        ]);
}
```

### 9. Test Validation Rules
```php
public function test_order_creation_validates_required_fields()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/api/orders', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['customer_id', 'items']);
}
```

### 10. Use Data Providers for Multiple Scenarios
```php
/**
 * @dataProvider invalidDataProvider
 */
public function test_validation_fails_with_invalid_data($field, $value)
{
    $data = Order::factory()->make()->toArray();
    $data[$field] = $value;
    
    $response = $this->post('/api/orders', $data);
    $response->assertJsonValidationErrors([$field]);
}

public static function invalidDataProvider(): array
{
    return [
        'empty customer_id' => ['customer_id', ''],
        'invalid customer_id' => ['customer_id', 'not-a-uuid'],
        'negative total' => ['total', -100],
    ];
}
```

## Running Module Tests

```bash
# Run all tests for a specific module
php artisan test --testsuite=ModuleName

# Run specific test file
php artisan test Modules/ModuleName/Tests/Unit/ServiceTest.php

# Run with coverage for the module
php artisan test --testsuite=ModuleName --coverage
```

## Test Coverage Requirements

- **Minimum coverage**: 80% for all modules
- **Critical paths**: 100% coverage for:
  - Authorization and authentication logic
  - Payment processing
  - Multi-tenancy isolation
  - Data validation
  - Financial calculations

## Common Pitfalls to Avoid

1. **Don't test framework functionality** - Focus on your business logic
2. **Don't use real external APIs** - Always mock external dependencies
3. **Don't rely on test execution order** - Each test must be independent
4. **Don't forget edge cases** - Test boundary conditions and error states
5. **Don't skip cleanup** - Use RefreshDatabase or database transactions
