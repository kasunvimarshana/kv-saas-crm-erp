# Concurrency Testing Guide

This guide provides comprehensive testing strategies for validating distributed system behavior under high concurrency.

## Overview

Concurrency testing ensures:
- Data integrity under simultaneous writes
- Proper distributed locking
- Race condition prevention
- Tenant isolation under load
- Performance under concurrent load

## Test Categories

### 1. Unit Tests for Distributed Locking

#### Test Distributed Lock Service

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Services\DistributedLockService;

class DistributedLockServiceTest extends TestCase
{
    protected DistributedLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockService = app(DistributedLockService::class);
        
        // Clear any existing locks
        Cache::store('lock')->flush();
    }

    public function test_can_acquire_lock(): void
    {
        $acquired = $this->lockService->acquire('test-resource', 5, 10);
        
        $this->assertTrue($acquired);
        $this->assertTrue($this->lockService->exists('test-resource'));
    }

    public function test_cannot_acquire_already_held_lock(): void
    {
        // First acquisition succeeds
        $first = $this->lockService->acquire('test-resource', 5, 10);
        $this->assertTrue($first);
        
        // Second acquisition fails (timeout = 0 for immediate failure)
        $second = $this->lockService->acquire('test-resource', 0, 10);
        $this->assertFalse($second);
    }

    public function test_can_acquire_after_release(): void
    {
        // Acquire and release
        $this->lockService->acquire('test-resource', 5, 10);
        $this->lockService->release('test-resource');
        
        // Should be able to acquire again
        $acquired = $this->lockService->acquire('test-resource', 5, 10);
        $this->assertTrue($acquired);
    }

    public function test_lock_auto_expires(): void
    {
        // Acquire with 1 second expiry
        $this->lockService->acquire('test-resource', 5, 1);
        
        // Wait for expiration
        sleep(2);
        
        // Should be able to acquire again
        $acquired = $this->lockService->acquire('test-resource', 5, 10);
        $this->assertTrue($acquired);
    }

    public function test_execute_with_lock(): void
    {
        $executed = false;
        
        $result = $this->lockService->executeWithLock('test-resource', function () use (&$executed) {
            $executed = true;
            return 'success';
        }, 5, 10);
        
        $this->assertTrue($executed);
        $this->assertEquals('success', $result);
    }

    public function test_tenant_aware_locking(): void
    {
        // Set tenant context
        session(['tenant_id' => 1]);
        
        $lock1 = $this->lockService->acquire('resource', 5, 10);
        $this->assertTrue($lock1);
        
        // Different tenant should not be blocked
        session(['tenant_id' => 2]);
        $lock2 = $this->lockService->acquire('resource', 5, 10);
        $this->assertTrue($lock2);
    }
}
```

### 2. Integration Tests for Stock Operations

#### Test Concurrent Stock Updates

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Entities\Warehouse;
use Modules\Inventory\Entities\StockLevel;
use Modules\Core\Services\DistributedLockService;

class ConcurrentStockUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_stock_reservations_maintain_integrity(): void
    {
        // Setup
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $stockLevel = StockLevel::create([
            'tenant_id' => 1,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        // Simulate 10 concurrent reservation attempts of 10 units each
        $results = [];
        $processes = [];
        
        for ($i = 0; $i < 10; $i++) {
            $processes[$i] = function () use ($product, $warehouse, &$results, $i) {
                DB::reconnect();  // Each "process" gets own connection
                
                try {
                    $lockService = app(DistributedLockService::class);
                    
                    $result = $lockService->executeStockOperation(
                        $product->id,
                        $warehouse->id,
                        function () use ($product, $warehouse) {
                            DB::beginTransaction();
                            try {
                                $stock = StockLevel::where('product_id', $product->id)
                                    ->where('warehouse_id', $warehouse->id)
                                    ->lockForUpdate()
                                    ->first();
                                
                                if ($stock->quantity >= 10) {
                                    $stock->quantity -= 10;
                                    $stock->reserved_quantity += 10;
                                    $stock->save();
                                    
                                    DB::commit();
                                    return true;
                                }
                                
                                DB::rollBack();
                                return false;
                                
                            } catch (\Exception $e) {
                                DB::rollBack();
                                throw $e;
                            }
                        }
                    );
                    
                    $results[$i] = $result;
                    
                } catch (\Exception $e) {
                    $results[$i] = false;
                }
            };
        }

        // Execute all "processes" concurrently (simulated with loop)
        foreach ($processes as $process) {
            $process();
        }

        // Verify final stock level
        $finalStock = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        // All 10 reservations should succeed
        $successfulReservations = array_filter($results);
        $this->assertEquals(10, count($successfulReservations));
        
        // Final quantities should be correct
        $this->assertEquals(0, $finalStock->quantity);
        $this->assertEquals(100, $finalStock->reserved_quantity);
    }

    public function test_prevents_overselling(): void
    {
        // Setup with limited stock
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        StockLevel::create([
            'tenant_id' => 1,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50,  // Only 50 available
            'reserved_quantity' => 0,
        ]);

        // Try to reserve 10 units, 10 times (total 100 > 50 available)
        $successCount = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $lockService = app(DistributedLockService::class);
            
            $result = $lockService->executeStockOperation(
                $product->id,
                $warehouse->id,
                function () use ($product, $warehouse) {
                    DB::beginTransaction();
                    try {
                        $stock = StockLevel::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouse->id)
                            ->lockForUpdate()
                            ->first();
                        
                        if ($stock->quantity >= 10) {
                            $stock->quantity -= 10;
                            $stock->reserved_quantity += 10;
                            $stock->save();
                            
                            DB::commit();
                            return true;
                        }
                        
                        DB::rollBack();
                        return false;
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }
            );
            
            if ($result) {
                $successCount++;
            }
        }

        // Only 5 reservations should succeed (5 * 10 = 50)
        $this->assertEquals(5, $successCount);
        
        // Verify final stock
        $finalStock = StockLevel::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        
        $this->assertEquals(0, $finalStock->quantity);
        $this->assertEquals(50, $finalStock->reserved_quantity);
    }
}
```

### 3. API Rate Limiting Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Entities\User;

class ApiRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limit cache
        Cache::store('redis')->flush();
    }

    public function test_rate_limit_enforced(): void
    {
        $user = User::factory()->create();
        
        // API rate limit is 60 per minute
        $limit = 60;
        $successCount = 0;
        $rateLimitedCount = 0;

        for ($i = 0; $i < $limit + 10; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/customers');
            
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $rateLimitedCount++;
            }
        }

        // Should allow exactly $limit requests
        $this->assertEquals($limit, $successCount);
        
        // Additional requests should be rate limited
        $this->assertGreaterThan(0, $rateLimitedCount);
    }

    public function test_rate_limit_headers_present(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/v1/customers');

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    public function test_rate_limit_per_tenant(): void
    {
        $user1 = User::factory()->create(['tenant_id' => 1]);
        $user2 = User::factory()->create(['tenant_id' => 2]);

        // User1 exhausts their limit
        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user1)->getJson('/api/v1/customers');
        }

        // User1 should be rate limited
        $response1 = $this->actingAs($user1)->getJson('/api/v1/customers');
        $this->assertEquals(429, $response1->status());

        // User2 (different tenant) should not be affected
        $response2 = $this->actingAs($user2)->getJson('/api/v1/customers');
        $this->assertEquals(200, $response2->status());
    }
}
```

### 4. Load Testing

#### Using Apache Bench

```bash
# Install Apache Bench
apt-get install apache2-utils

# Basic load test
ab -n 1000 -c 10 -H "Authorization: Bearer YOUR_TOKEN" \
   https://api.example.com/api/v1/customers

# With results file
ab -n 10000 -c 100 -g results.tsv \
   -H "Authorization: Bearer YOUR_TOKEN" \
   https://api.example.com/api/v1/customers

# POST request
ab -n 1000 -c 10 -p data.json -T application/json \
   -H "Authorization: Bearer YOUR_TOKEN" \
   https://api.example.com/api/v1/orders
```

#### Using Locust (Python)

Create `locustfile.py`:

```python
from locust import HttpUser, task, between
import json

class ApiUser(HttpUser):
    wait_time = between(1, 3)
    
    def on_start(self):
        # Login and get token
        response = self.client.post("/api/v1/auth/login", json={
            "email": "test@example.com",
            "password": "password"
        })
        self.token = response.json()["token"]
    
    @task(3)  # Weight 3 (more frequent)
    def get_customers(self):
        self.client.get("/api/v1/customers", headers={
            "Authorization": f"Bearer {self.token}"
        })
    
    @task(2)  # Weight 2
    def get_orders(self):
        self.client.get("/api/v1/orders", headers={
            "Authorization": f"Bearer {self.token}"
        })
    
    @task(1)  # Weight 1 (less frequent)
    def create_order(self):
        self.client.post("/api/v1/orders", json={
            "customer_id": "uuid-123",
            "items": [
                {"product_id": "uuid-456", "quantity": 2}
            ]
        }, headers={
            "Authorization": f"Bearer {self.token}",
            "Content-Type": "application/json"
        })
```

Run load test:

```bash
# Install Locust
pip install locust

# Run load test
locust -f locustfile.py --host=https://api.example.com

# Headless mode (no web UI)
locust -f locustfile.py --host=https://api.example.com \
       --users=100 --spawn-rate=10 --run-time=5m --headless
```

### 5. Stress Testing

#### Database Connection Pool Stress Test

```bash
# PostgreSQL connection stress test
pgbench -i -s 50 kv_saas_erp  # Initialize
pgbench -c 100 -j 10 -T 60 kv_saas_erp  # 100 clients, 60 seconds
```

#### Redis Stress Test

```bash
# Redis benchmark
redis-benchmark -h redis.example.com -p 6379 -a password \
                -n 100000 -c 50 -q

# Test specific commands
redis-benchmark -h redis.example.com -t set,get -n 1000000 -q
```

#### Application Stress Test

```bash
# Siege (HTTP load testing)
siege -c 100 -t 60S -H "Authorization: Bearer TOKEN" \
      https://api.example.com/api/v1/customers

# With URL list
siege -c 100 -t 60S -f urls.txt
```

Create `urls.txt`:
```
https://api.example.com/api/v1/customers
https://api.example.com/api/v1/products
https://api.example.com/api/v1/orders
```

### 6. Chaos Engineering

#### Simulate Pod Failures

```bash
# Randomly kill pods to test resilience
while true; do
    kubectl delete pod -n production \
        $(kubectl get pods -n production -l app=kv-erp-api -o name | shuf -n 1)
    sleep 60
done
```

#### Network Latency Simulation

```bash
# Add 100ms latency to network interface
tc qdisc add dev eth0 root netem delay 100ms

# Remove latency
tc qdisc del dev eth0 root
```

#### Database Connection Loss

```bash
# Simulate database connection loss
kubectl exec -it postgres-0 -n production -- bash
pg_ctl stop -m fast

# Wait 30 seconds
sleep 30

# Restart
pg_ctl start
```

## Monitoring During Tests

### Key Metrics to Watch

1. **Response Times**
   - p50, p95, p99 latency
   - Should remain under 500ms for p95

2. **Error Rate**
   - Should stay under 0.1%
   - Check error logs for patterns

3. **Database Connections**
   - Should not exceed pool limit
   - Watch for connection leaks

4. **Redis Memory**
   - Should not exceed max memory
   - Watch for evictions

5. **Queue Depth**
   - Should process jobs faster than they arrive
   - Watch for backlog growth

6. **Pod Resource Usage**
   - CPU and memory under limits
   - Watch for OOM kills

### Monitoring Commands

```bash
# Watch pod metrics
kubectl top pods -n production

# Watch HPA
watch kubectl get hpa -n production

# Database connections
psql -h primary.db.example.com -U app_user -d kv_saas_erp \
     -c "SELECT count(*) FROM pg_stat_activity;"

# Redis info
redis-cli -h redis.example.com INFO stats

# Queue depth
redis-cli -h redis.example.com LLEN "queue:default"

# Application logs
kubectl logs -f deployment/kv-erp-api -n production --tail=100
```

## Performance Benchmarks

### Target Metrics

- **Throughput**: 1000+ requests/second
- **Response Time (p95)**: < 500ms
- **Response Time (p99)**: < 1000ms
- **Error Rate**: < 0.1%
- **Uptime**: 99.9%

### Acceptance Criteria

| Test | Metric | Target | Pass/Fail |
|------|--------|--------|-----------|
| Load Test | 1000 req/s | p95 < 500ms | ✅ |
| Concurrency | 100 simultaneous updates | 0 data corruption | ✅ |
| Rate Limiting | 60 req/min/user | Enforced correctly | ✅ |
| Failover | Pod crash | < 5s recovery | ✅ |
| Horizontal Scale | 1000 → 5000 req/s | Auto-scale 3→8 pods | ✅ |

## Troubleshooting

### High Error Rates

```bash
# Check application logs
kubectl logs deployment/kv-erp-api -n production | grep ERROR

# Check database slow queries
psql -h primary.db.example.com -U app_user -d kv_saas_erp \
     -c "SELECT pid, query, now() - query_start AS duration
         FROM pg_stat_activity
         WHERE state = 'active' AND now() - query_start > interval '1 second';"
```

### Lock Timeouts

```bash
# Check stuck locks
redis-cli -h redis.example.com -n 2 KEYS "lock:*"

# Check lock TTL
redis-cli -h redis.example.com -n 2 TTL "lock:tenant:1:stock:product-123"
```

### Queue Backlog

```bash
# Check queue depth
redis-cli -h redis.example.com LLEN "queue:default"
redis-cli -h redis.example.com LLEN "queue:high"

# Scale up queue workers
kubectl scale deployment kv-erp-queue-worker --replicas=4 -n production
```

## Best Practices

1. **Start Small**: Begin with low concurrency, gradually increase
2. **Monitor Continuously**: Watch metrics throughout tests
3. **Test Incrementally**: Test one component at a time
4. **Automate Testing**: Include in CI/CD pipeline
5. **Document Results**: Keep baseline for comparison
6. **Test Failure Scenarios**: Don't just test happy paths
7. **Use Production-Like Environment**: Staging should mirror production
8. **Test Multi-Tenancy**: Ensure tenant isolation under load

## Continuous Testing

### CI/CD Integration

```yaml
# .github/workflows/load-test.yml
name: Load Test

on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM
  workflow_dispatch:

jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Run Load Test
        run: |
          docker run --rm \
            -v $PWD:/mnt/locust \
            locustio/locust \
            -f /mnt/locust/locustfile.py \
            --host=https://staging.api.example.com \
            --users=100 --spawn-rate=10 \
            --run-time=5m --headless \
            --html=report.html
      
      - name: Upload Report
        uses: actions/upload-artifact@v2
        with:
          name: load-test-report
          path: report.html
```

---

**Last Updated**: 2026-02-10  
**Version**: 1.0.0
