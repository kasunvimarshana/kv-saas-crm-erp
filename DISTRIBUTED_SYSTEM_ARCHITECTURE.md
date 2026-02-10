# Distributed System Architecture

---

**⚠️ IMPLEMENTATION PRINCIPLE**: All distributed system features use native Laravel and Redis. No third-party packages beyond core Laravel framework.

---

## Overview

This document describes the comprehensive distributed system architecture implemented in kv-saas-crm-erp to support:

- **Multi-User Concurrency**: Thousands of simultaneous users with data consistency
- **Horizontal Scalability**: Seamless scaling across multiple application servers
- **Fault Tolerance**: Graceful degradation and automatic recovery
- **Consistent Data Access**: ACID guarantees across distributed operations
- **Strict Tenant Isolation**: Zero cross-tenant data leakage
- **Authorization**: Fine-grained access control at every layer
- **Data Integrity**: Pessimistic and optimistic locking mechanisms

## Architecture Components

### 1. Distributed Cache (Redis)

**Configuration**: `config/cache.php`

**Redis Databases**:
- **DB 0**: Default operations and queue jobs
- **DB 1**: Application cache (query results, session data)
- **DB 2**: Distributed locks (concurrency control)
- **DB 3**: User sessions (cross-server session sharing)

**Features**:
- Cache key prefixing: `kv_erp_cache`
- Tenant-aware caching with automatic isolation
- Cache invalidation strategies
- Connection pooling for high throughput

**Usage**:
```php
// Cache with automatic tenant isolation
Cache::remember('customers', 3600, function () {
    return Customer::all();
});

// Distributed locking
Cache::lock('stock:product-123:warehouse-456', 10)->get(function () {
    // Critical section - atomic operation
});
```

### 2. Queue System (Redis)

**Configuration**: `config/queue.php`

**Queue Priorities**:
1. **High Priority**: Critical operations (payments, stock reservations)
2. **Default Priority**: Standard async tasks (emails, notifications)
3. **Low Priority**: Background processing (reports, analytics)

**Features**:
- Automatic retry with exponential backoff
- Failed job tracking in database
- Job batching for bulk operations
- Job chaining for sequential workflows

**Queue Workers**:
```bash
# High priority queue (critical operations)
php artisan queue:work redis --queue=high --tries=3 --timeout=60

# Default priority queue
php artisan queue:work redis --queue=default --tries=3 --timeout=90

# Low priority queue (background tasks)
php artisan queue:work redis --queue=low --tries=5 --timeout=300
```

**Usage**:
```php
// Dispatch to high priority queue
dispatch(new ReserveStockJob($order))->onQueue('high');

// Dispatch to default queue
dispatch(new SendInvoiceEmail($invoice));

// Dispatch with delay
dispatch(new GenerateReportJob($params))->onQueue('low')->delay(now()->addMinutes(5));
```

### 3. Database Configuration

**Configuration**: `config/database.php`

**Connection Types**:

#### 3.1 Primary Connection (Read/Write)
```php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'kv_saas_erp'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'sticky' => true,  // Ensures read-your-writes consistency
]
```

#### 3.2 Read Replicas (Horizontal Scaling)
```php
'pgsql_read' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '127.0.0.1'),
            env('DB_READ_HOST_2', '127.0.0.1'),
        ],
    ],
    'write' => [
        'host' => [env('DB_HOST', '127.0.0.1')],
    ],
    'sticky' => true,  // Read from primary after write
]
```

**Sticky Sessions**: After a write operation, subsequent reads within the same request go to the primary database, ensuring read-your-writes consistency.

**Usage**:
```php
// Write to primary
DB::connection('pgsql')->table('orders')->insert($data);

// Subsequent reads in same request use primary (sticky)
$orders = DB::connection('pgsql_read')->table('orders')->get();
```

### 4. Session Management

**Configuration**: `config/session.php`

**Driver**: Redis (for cross-server session sharing)

**Features**:
- Distributed session storage across all app instances
- Automatic session expiration (120 minutes default)
- Secure cookie configuration (httpOnly, secure, sameSite)
- Session encryption for sensitive data

**Environment Variables**:
```env
SESSION_DRIVER=redis
SESSION_CONNECTION=session
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

**Session Structure**:
```php
// Tenant context stored in session
Session::put('tenant_id', $tenant->id);
Session::put('user_id', $user->id);
Session::put('permissions', $user->permissions);

// Automatic propagation across all servers via Redis
```

### 5. Distributed Locking Service

**Service**: `Modules/Core/Services/DistributedLockService.php`

**Purpose**: Provides atomic operations across multiple application servers using Redis-based distributed locks.

**Features**:
- Pessimistic locking with automatic expiration
- Deadlock prevention
- Lock renewal for long-running operations
- Tenant-aware lock isolation
- Specialized locks for stock and financial operations

**API**:
```php
use Modules\Core\Services\DistributedLockService;

$lockService = app(DistributedLockService::class);

// Basic locking
if ($lockService->acquire('my-resource', 10, 30)) {
    try {
        // Critical section
        performAtomicOperation();
    } finally {
        $lockService->release('my-resource');
    }
}

// Execute with automatic lock management
$result = $lockService->executeWithLock('my-resource', function () {
    return performAtomicOperation();
}, 10, 30);

// Stock-specific locking
$lockService->executeStockOperation($productId, $warehouseId, function () use ($quantity) {
    $stockLevel = StockLevel::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->lockForUpdate()  // Database-level pessimistic lock
        ->first();
    
    $stockLevel->quantity -= $quantity;
    $stockLevel->save();
});
```

**Lock Types**:

1. **Generic Lock**: `lock:tenant-id:resource-key`
2. **Stock Lock**: `lock:tenant-id:stock:product-id:warehouse-id`
3. **Account Lock**: `lock:tenant-id:account:account-id`

**Timeouts**:
- Stock operations: 5 seconds acquire timeout, 10 seconds expiry
- Account operations: 10 seconds acquire timeout, 30 seconds expiry
- Generic operations: 10 seconds acquire timeout, 30 seconds expiry

### 6. API Rate Limiting

**Middleware**: `Modules/Core/Http/Middleware/ApiRateLimiter.php`

**Purpose**: Prevents API abuse and ensures fair resource allocation across all users in a distributed environment.

**Features**:
- Per-user rate limiting
- Per-tenant rate limiting
- Per-IP rate limiting for unauthenticated requests
- Sliding window algorithm
- Distributed across all app servers via Redis
- Standard HTTP 429 responses with Retry-After headers

**Configuration**:
```env
API_RATE_LIMIT=60  # Requests per minute
```

**Rate Limit Keys**:
```
rate_limit:tenant:{tenant-id}:user:{user-id}:route:{path}
rate_limit:tenant:{tenant-id}:ip:{ip-address}:route:{path}
```

**Response Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1709876543
Retry-After: 23
```

**Usage in Routes**:
```php
Route::middleware(['api', 'auth:sanctum', ApiRateLimiter::class.':60'])
    ->group(function () {
        Route::apiResource('customers', CustomerController::class);
    });

// Different limits for different endpoints
Route::middleware(['api', 'auth:sanctum', ApiRateLimiter::class.':120'])
    ->post('/orders', [OrderController::class, 'store']);
```

### 7. Pessimistic Locking Patterns

**Purpose**: Prevent race conditions in high-concurrency scenarios using database-level locks.

**Implementation**:
```php
use Illuminate\Support\Facades\DB;

// Stock reservation with pessimistic locking
DB::transaction(function () use ($productId, $warehouseId, $quantity) {
    $stockLevel = StockLevel::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->lockForUpdate()  // SELECT ... FOR UPDATE
        ->first();
    
    if ($stockLevel->quantity < $quantity) {
        throw new InsufficientStockException();
    }
    
    $stockLevel->quantity -= $quantity;
    $stockLevel->reserved_quantity += $quantity;
    $stockLevel->save();
});

// Account balance update with pessimistic locking
DB::transaction(function () use ($accountId, $amount) {
    $account = Account::where('id', $accountId)
        ->lockForUpdate()
        ->first();
    
    $account->balance += $amount;
    $account->save();
});
```

**When to Use**:
1. Stock updates (reservations, releases, adjustments)
2. Financial transactions (payments, transfers, balance updates)
3. Sequential number generation (invoice numbers, order numbers)
4. Concurrent write operations on same resource

### 8. Optimistic Locking Patterns

**Purpose**: Detect concurrent modifications using version numbers or timestamps.

**Implementation**:
```php
// Add version column to migration
$table->unsignedBigInteger('version')->default(0);

// Model with optimistic locking
class Product extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::updating(function ($model) {
            $originalVersion = $model->getOriginal('version');
            
            // Check if someone else modified it
            $current = static::where('id', $model->id)
                ->where('version', $originalVersion)
                ->first();
            
            if (!$current) {
                throw new ConcurrentModificationException(
                    'Record was modified by another user. Please refresh and try again.'
                );
            }
            
            // Increment version
            $model->version = $originalVersion + 1;
        });
    }
}

// Usage
try {
    $product = Product::find($id);
    $product->price = $newPrice;
    $product->save();
} catch (ConcurrentModificationException $e) {
    // Handle conflict - show user that data changed
    return response()->json(['error' => $e->getMessage()], 409);
}
```

### 9. Event-Driven Architecture

**Purpose**: Decouple modules and enable asynchronous processing across distributed systems.

**Features**:
- Domain events for business logic
- Async event listeners via queues
- Cross-module integration without tight coupling
- Eventual consistency patterns

**Example Flow**:
```
1. User confirms sales order
2. SalesOrderConfirmed event fired
3. Event listeners (async via queue):
   - ReserveStockListener (Inventory module)
   - CreateAccountingEntryListener (Accounting module)
   - SendOrderConfirmationListener (Notification module)
4. Each listener processes independently
5. System remains responsive while work completes
```

**Implementation**:
```php
// Event
class SalesOrderConfirmed
{
    public function __construct(public SalesOrder $order) {}
}

// Async Listener (queued automatically)
class ReserveStockListener implements ShouldQueue
{
    public $tries = 3;
    public $backoff = 10;
    
    public function handle(SalesOrderConfirmed $event): void
    {
        $lockService = app(DistributedLockService::class);
        
        foreach ($event->order->lines as $line) {
            $lockService->executeStockOperation(
                $line->product_id,
                $event->order->warehouse_id,
                function () use ($line) {
                    DB::transaction(function () use ($line) {
                        $stockLevel = StockLevel::where('product_id', $line->product_id)
                            ->where('warehouse_id', $line->warehouse_id)
                            ->lockForUpdate()
                            ->first();
                        
                        $stockLevel->reserved_quantity += $line->quantity;
                        $stockLevel->save();
                    });
                }
            );
        }
    }
}
```

### 10. Health Checks & Monitoring

**Endpoint**: `/api/health`

**Purpose**: Load balancer health checks for multi-server deployments.

**Implementation**:
```php
// routes/api.php
Route::get('/health', function () {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'checks' => [],
    ];
    
    // Check database connectivity
    try {
        DB::connection()->getPdo();
        $health['checks']['database'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['database'] = 'failed';
        $health['status'] = 'unhealthy';
    }
    
    // Check Redis connectivity
    try {
        Cache::store('redis')->get('health_check');
        $health['checks']['redis'] = 'ok';
    } catch (\Exception $e) {
        $health['checks']['redis'] = 'failed';
        $health['status'] = 'unhealthy';
    }
    
    // Check queue worker status
    $queueSize = Cache::store('redis')->get('queue:default:size', 0);
    $health['checks']['queue'] = $queueSize < 1000 ? 'ok' : 'degraded';
    
    $statusCode = $health['status'] === 'healthy' ? 200 : 503;
    return response()->json($health, $statusCode);
});
```

**Load Balancer Configuration (Nginx)**:
```nginx
upstream backend {
    least_conn;  # Route to least busy server
    
    server app1.example.com:8000 max_fails=3 fail_timeout=30s;
    server app2.example.com:8000 max_fails=3 fail_timeout=30s;
    server app3.example.com:8000 max_fails=3 fail_timeout=30s;
    
    # Health check every 5 seconds
    check interval=5000 rise=2 fall=3 timeout=3000 type=http;
    check_http_send "GET /api/health HTTP/1.0\r\n\r\n";
    check_http_expect_alive http_2xx http_3xx;
}

server {
    listen 80;
    server_name api.example.com;
    
    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Sticky sessions using IP hash
        ip_hash;
    }
}
```

## Deployment Patterns

### 1. Multi-Server Deployment

**Architecture**:
```
                     ┌─────────────────┐
                     │  Load Balancer  │
                     │     (Nginx)     │
                     └────────┬────────┘
                              │
         ┌────────────────────┼────────────────────┐
         │                    │                    │
    ┌────▼─────┐         ┌────▼─────┐        ┌────▼─────┐
    │  App 1   │         │  App 2   │        │  App 3   │
    │ Laravel  │         │ Laravel  │        │ Laravel  │
    └────┬─────┘         └────┬─────┘        └────┬─────┘
         │                    │                    │
         └────────────────────┼────────────────────┘
                              │
         ┌────────────────────┴────────────────────┐
         │                                         │
    ┌────▼─────┐                             ┌────▼─────┐
    │PostgreSQL│                             │  Redis   │
    │ Primary  │                             │ Cluster  │
    └────┬─────┘                             └──────────┘
         │
    ┌────▼─────┐
    │PostgreSQL│
    │ Replica  │
    └──────────┘
```

### 2. Environment Variables

**Production Configuration** (`.env`):
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com

# Database (Primary)
DB_CONNECTION=pgsql
DB_HOST=primary.db.example.com
DB_PORT=5432
DB_DATABASE=kv_saas_erp
DB_USERNAME=app_user
DB_PASSWORD=secure_password
DB_TIMEOUT=5
DB_PERSISTENT=false

# Database (Read Replicas)
DB_READ_HOST_1=replica1.db.example.com
DB_READ_HOST_2=replica2.db.example.com

# Redis (High Availability)
REDIS_HOST=redis.example.com
REDIS_PORT=6379
REDIS_PASSWORD=secure_redis_password
REDIS_CLUSTER=redis
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_LOCK_DB=2
REDIS_SESSION_DB=3

# Cache
CACHE_STORE=redis
CACHE_PREFIX=prod_kv_erp_

# Queue
QUEUE_CONNECTION=redis
QUEUE_RETRY_AFTER=90

# Session
SESSION_DRIVER=redis
SESSION_CONNECTION=session
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Rate Limiting
API_RATE_LIMIT=120

# Multi-Tenancy
TENANCY_ENABLED=true
```

### 3. Scaling Strategy

**Horizontal Scaling**:
1. Add application servers behind load balancer
2. All servers connect to same PostgreSQL and Redis
3. Session sharing via Redis ensures sticky sessions work
4. Queue workers can run on any server or dedicated worker nodes
5. No local file storage - use S3 or shared filesystem

**Vertical Scaling**:
1. Increase PostgreSQL resources (CPU, RAM, IOPS)
2. Add read replicas for read-heavy workloads
3. Increase Redis memory for larger cache
4. Optimize database indexes and queries

**Auto-Scaling (Kubernetes)**:
```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: kv-erp-api
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: kv-erp-api
  minReplicas: 3
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
```

## Performance Optimization

### 1. Database Optimization

**Indexes**:
- Foreign keys: Automatic indexes
- Frequently queried columns: `tenant_id`, `created_at`, `status`
- Composite indexes: `(tenant_id, created_at)`, `(tenant_id, status)`

**Query Optimization**:
```php
// Bad: N+1 query problem
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name;  // N queries
}

// Good: Eager loading
$orders = Order::with('customer')->get();
foreach ($orders as $order) {
    echo $order->customer->name;  // 2 queries total
}

// Better: Select only needed columns
$orders = Order::with('customer:id,name')->select('id', 'customer_id', 'total')->get();
```

**Connection Pooling (PgBouncer)**:
```ini
[databases]
kv_saas_erp = host=primary.db.example.com port=5432 dbname=kv_saas_erp

[pgbouncer]
listen_addr = 127.0.0.1
listen_port = 6432
auth_type = md5
auth_file = /etc/pgbouncer/userlist.txt
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 25
reserve_pool_size = 5
reserve_pool_timeout = 3
```

### 2. Caching Strategy

**Cache Layers**:
1. **Application Cache**: Query results, computed values
2. **HTTP Cache**: API responses with ETags
3. **CDN Cache**: Static assets, public content

**Cache Tags**:
```php
// Tag-based cache invalidation
Cache::tags(['customers', 'tenant:'.$tenantId])->remember('customer:' . $id, 3600, function () use ($id) {
    return Customer::find($id);
});

// Invalidate all customer caches for tenant
Cache::tags(['customers', 'tenant:'.$tenantId])->flush();
```

### 3. Queue Optimization

**Job Batching**:
```php
// Process 1000 records efficiently
Bus::batch([
    new ProcessRecordJob($records->slice(0, 100)),
    new ProcessRecordJob($records->slice(100, 100)),
    // ... 10 batches total
])->dispatch();
```

**Job Chaining**:
```php
// Sequential workflow
Bus::chain([
    new ValidateDataJob($data),
    new ProcessDataJob($data),
    new SendNotificationJob($data),
])->dispatch();
```

## Security Considerations

### 1. Tenant Isolation

**Global Scope Enforcement**:
```php
// Tenantable trait ensures ALL queries filtered by tenant
Customer::all();  // WHERE tenant_id = ?

// Admin override when needed
Customer::withoutTenancy()->get();
```

### 2. Rate Limiting

**Per-Tenant Limits**:
- Prevents single tenant from consuming all resources
- Different limits for different subscription tiers
- Graceful degradation under load

### 3. Input Validation

**Form Requests**:
- All API endpoints use Form Request validation
- Sanitization of user input
- Type checking and bounds validation

### 4. Authorization

**Policy-Based**:
- Every action checked via policies
- Tenant ownership verified
- Permission checks at every layer

## Monitoring & Observability

### 1. Metrics to Track

**Application Metrics**:
- Request rate (requests/second)
- Response time (p50, p95, p99)
- Error rate (4xx, 5xx)
- Queue size and processing time
- Active users and sessions

**Infrastructure Metrics**:
- CPU usage per server
- Memory usage per server
- Database connection pool utilization
- Redis memory usage
- Network throughput

**Business Metrics**:
- Orders per minute
- Revenue per hour
- Active tenants
- User registrations
- API usage per tenant

### 2. Logging

**Structured Logging**:
```php
Log::info('Order created', [
    'order_id' => $order->id,
    'tenant_id' => $order->tenant_id,
    'customer_id' => $order->customer_id,
    'total' => $order->total,
    'timestamp' => now()->toIso8601String(),
]);
```

**Log Aggregation**:
- Centralized logging (ELK Stack, CloudWatch)
- Correlation IDs for tracing requests across services
- Tenant context in every log entry

### 3. Alerting

**Critical Alerts**:
- Database connection failures
- Redis unavailability
- Queue backlog > 1000 jobs
- Error rate > 5%
- Response time p95 > 2 seconds

## Failover & Disaster Recovery

### 1. Database Failover

**Automatic Failover**:
- Read replicas promoted to primary
- Connection string updated automatically
- Minimal downtime (< 30 seconds)

### 2. Redis High Availability

**Redis Sentinel**:
- Automatic master election
- Slave promotion on failure
- Client library handles failover

### 3. Backup Strategy

**Database Backups**:
- Full backup daily
- Transaction log backup every 15 minutes
- Point-in-time recovery capability
- Cross-region backup replication

**Redis Persistence**:
- AOF (Append-Only File) for durability
- RDB snapshots every hour
- Replication to standby instance

## Testing Distributed Systems

### 1. Concurrency Tests

```php
public function test_concurrent_stock_updates_maintain_integrity()
{
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    
    StockLevel::create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 100,
    ]);
    
    // Simulate 10 concurrent orders
    $promises = [];
    for ($i = 0; $i < 10; $i++) {
        $promises[] = dispatch(new ReserveStockJob($product->id, $warehouse->id, 10));
    }
    
    // Wait for all jobs to complete
    Promise::all($promises)->wait();
    
    // Verify final stock level
    $finalStock = StockLevel::where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->first();
    
    $this->assertEquals(0, $finalStock->quantity);
    $this->assertEquals(100, $finalStock->reserved_quantity);
}
```

### 2. Load Testing

```bash
# Apache Bench
ab -n 10000 -c 100 -H "Authorization: Bearer token" https://api.example.com/api/v1/customers

# Locust (Python)
locust -f load_test.py --host=https://api.example.com --users=1000 --spawn-rate=10
```

### 3. Chaos Engineering

**Simulate Failures**:
- Kill random application pods
- Introduce network latency
- Disconnect from database temporarily
- Fill up Redis memory
- Measure recovery time and data consistency

## Best Practices

### 1. Always Use Distributed Locks for:
- Stock updates
- Balance updates
- Sequential number generation
- Concurrent writes to same resource

### 2. Always Use Transactions for:
- Multi-step operations
- Operations affecting multiple tables
- Financial calculations
- Stock movements

### 3. Always Use Queues for:
- Email sending
- Report generation
- External API calls
- Heavy computations

### 4. Always Cache:
- Frequently accessed read-only data
- Expensive queries
- External API responses
- Computed values

### 5. Never:
- Store sessions in local files
- Use file-based cache in production
- Query database in loops
- Perform heavy operations synchronously
- Trust user input without validation

## Conclusion

This distributed system architecture enables kv-saas-crm-erp to:

✅ **Scale horizontally** by adding application servers  
✅ **Handle thousands of concurrent users** with data consistency  
✅ **Maintain strict tenant isolation** at every layer  
✅ **Provide fault tolerance** through redundancy  
✅ **Ensure data integrity** with pessimistic and optimistic locking  
✅ **Deliver consistent performance** through caching and read replicas  
✅ **Support asynchronous processing** via queues  
✅ **Enforce rate limits** to prevent abuse  
✅ **Enable monitoring and observability** for production operations  

All using **native Laravel features** and Redis - no third-party packages required.
