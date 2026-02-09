---
applyTo: "**/Modules/**/Http/Controllers/**/*.php"
---

# API Controller Requirements

When writing API controllers for Laravel modules, follow these guidelines:

## Controller Structure

### 1. Use Repository Pattern
Never use Eloquent models directly in controllers. Always inject repositories:

```php
class OrderController extends Controller
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CreateOrderService $createOrderService
    ) {}
    
    public function store(CreateOrderRequest $request)
    {
        $order = $this->createOrderService->execute($request);
        return new OrderResource($order);
    }
}
```

### 2. Use Form Requests for Validation
Never validate in controllers. Always use Form Request classes:

```php
// Good
public function store(CreateOrderRequest $request)
{
    $validated = $request->validated();
    // Process validated data
}

// Avoid
public function store(Request $request)
{
    $validated = $request->validate([...]);
    // Validation should be in Form Request
}
```

### 3. Use Service Classes for Business Logic
Controllers should be thin - delegate business logic to services:

```php
// Good - Thin controller
public function store(CreateOrderRequest $request)
{
    $order = $this->createOrderService->execute($request);
    return new OrderResource($order);
}

// Avoid - Fat controller with business logic
public function store(CreateOrderRequest $request)
{
    DB::beginTransaction();
    try {
        $order = Order::create([...]);
        foreach ($items as $item) {
            // Complex business logic here
        }
        // More complex logic
        DB::commit();
        return new OrderResource($order);
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### 4. Use API Resources for Responses
Always use Resource classes for consistent API responses:

```php
// Single resource
return new OrderResource($order);

// Collection
return OrderResource::collection($orders);

// With pagination
return OrderResource::collection(
    $this->orderRepository->paginate($request->input('per_page', 15))
);
```

### 5. Implement Proper HTTP Status Codes
Use appropriate status codes for different scenarios:

```php
// 200 OK - Successful GET, PUT, PATCH
return response()->json($data, 200);

// 201 Created - Successful POST
return (new OrderResource($order))
    ->response()
    ->setStatusCode(201);

// 204 No Content - Successful DELETE
return response()->noContent();

// 400 Bad Request - Client error
return response()->json(['error' => 'Invalid input'], 400);

// 401 Unauthorized - Not authenticated
return response()->json(['error' => 'Unauthenticated'], 401);

// 403 Forbidden - Not authorized
return response()->json(['error' => 'Unauthorized'], 403);

// 404 Not Found
return response()->json(['error' => 'Resource not found'], 404);

// 422 Unprocessable Entity - Validation failed
// (Laravel handles this automatically with Form Requests)

// 500 Internal Server Error - Server error
return response()->json(['error' => 'Server error'], 500);
```

### 6. Use Route Model Binding
Let Laravel resolve models automatically:

```php
// routes/api.php
Route::get('/orders/{order}', [OrderController::class, 'show']);

// Controller
public function show(Order $order)
{
    $this->authorize('view', $order);
    return new OrderResource($order);
}
```

### 7. Implement Authorization
Always check permissions using policies:

```php
public function update(UpdateOrderRequest $request, Order $order)
{
    $this->authorize('update', $order);
    
    $order = $this->updateOrderService->execute($order, $request);
    return new OrderResource($order);
}

public function destroy(Order $order)
{
    $this->authorize('delete', $order);
    
    $this->orderRepository->delete($order);
    return response()->noContent();
}
```

### 8. Handle Exceptions Properly
Let services throw exceptions, catch them in controllers if needed:

```php
public function store(CreateOrderRequest $request)
{
    try {
        $order = $this->createOrderService->execute($request);
        return (new OrderResource($order))->response()->setStatusCode(201);
    } catch (InsufficientStockException $e) {
        return response()->json([
            'error' => 'Insufficient stock',
            'message' => $e->getMessage()
        ], 400);
    } catch (\Exception $e) {
        Log::error('Order creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => 'Failed to create order'
        ], 500);
    }
}
```

### 9. Support Query Parameters
Use spatie/laravel-query-builder for filtering, sorting, and including relationships:

```php
use Spatie\QueryBuilder\QueryBuilder;

public function index(Request $request)
{
    $orders = QueryBuilder::for(Order::class)
        ->allowedFilters(['status', 'customer_id', 'created_at'])
        ->allowedSorts(['created_at', 'total', 'order_number'])
        ->allowedIncludes(['customer', 'items', 'items.product'])
        ->paginate($request->input('per_page', 15));
    
    return OrderResource::collection($orders);
}
```

### 10. Add API Documentation
Use Swagger/OpenAPI annotations on all controller methods:

```php
/**
 * @OA\Get(
 *     path="/api/v1/orders/{id}",
 *     summary="Get order by ID",
 *     tags={"Orders"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(ref="#/components/schemas/OrderResource")
 *     ),
 *     @OA\Response(response=404, description="Order not found"),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
public function show(Order $order)
{
    $this->authorize('view', $order);
    return new OrderResource($order);
}
```

## RESTful Controller Template

Standard RESTful controller structure:

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Sales\Entities\Order;
use Modules\Sales\Http\Requests\CreateOrderRequest;
use Modules\Sales\Http\Requests\UpdateOrderRequest;
use Modules\Sales\Http\Resources\OrderResource;
use Modules\Sales\Repositories\OrderRepositoryInterface;
use Modules\Sales\Services\CreateOrderService;
use Modules\Sales\Services\UpdateOrderService;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends Controller
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CreateOrderService $createOrderService,
        private UpdateOrderService $updateOrderService
    ) {}

    public function index(Request $request): ResourceCollection
    {
        $orders = QueryBuilder::for(Order::class)
            ->allowedFilters(['status', 'customer_id'])
            ->allowedSorts(['created_at', 'total'])
            ->allowedIncludes(['customer', 'items'])
            ->paginate($request->input('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->createOrderService->execute($request);
        
        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        $this->authorize('view', $order);
        return new OrderResource($order);
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $this->authorize('update', $order);
        
        $order = $this->updateOrderService->execute($order, $request);
        return new OrderResource($order);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);
        
        $this->orderRepository->delete($order);
        return response()->noContent();
    }
}
```

## API Versioning

Always use versioned routes:

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('orders', OrderController::class);
});
```

## Rate Limiting

Apply rate limiting to API routes:

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('orders', OrderController::class);
});
```

## Common Pitfalls to Avoid

1. **Don't put business logic in controllers** - Use service classes
2. **Don't query the database directly** - Use repositories
3. **Don't validate in controllers** - Use Form Requests
4. **Don't return raw Eloquent models** - Use API Resources
5. **Don't forget authorization checks** - Use policies and `authorize()`
6. **Don't forget to add Swagger documentation** - Document all endpoints
7. **Don't forget tenant isolation** - Always ensure tenant context
8. **Don't catch exceptions unless you have specific handling** - Let global handler catch them
