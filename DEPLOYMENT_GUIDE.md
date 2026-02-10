# Deployment Guide for Distributed System

This guide provides step-by-step instructions for deploying kv-saas-crm-erp in a distributed, horizontally scalable configuration.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Architecture Overview](#architecture-overview)
3. [Local Development Setup](#local-development-setup)
4. [Production Deployment](#production-deployment)
5. [Kubernetes Deployment](#kubernetes-deployment)
6. [Monitoring & Operations](#monitoring--operations)
7. [Troubleshooting](#troubleshooting)

## Prerequisites

### Required Services
- **PostgreSQL 14+**: Primary database
- **Redis 7+**: Cache, sessions, queues, distributed locks
- **Nginx**: Load balancer (production only)
- **Docker & Kubernetes**: Container orchestration (production)

### Required Tools
- PHP 8.2+
- Composer 2.x
- Docker 20+
- kubectl (for Kubernetes deployments)

## Architecture Overview

```
┌─────────────────┐
│  Load Balancer  │
│     (Nginx)     │
└────────┬────────┘
         │
    ┌────┴────┬────────┬────────┐
    │         │        │        │
┌───▼──┐  ┌───▼──┐  ┌───▼──┐  ┌───▼──┐
│App 1 │  │App 2 │  │App 3 │  │App N │
└───┬──┘  └───┬──┘  └───┬──┘  └───┬──┘
    │         │        │        │
    └────┬────┴────────┴────────┘
         │
    ┌────▼────┐       ┌────────┐
    │PostgreSQL│       │ Redis  │
    │+ Replicas│       │Cluster │
    └─────────┘       └────────┘
```

### Key Components

1. **Application Servers (3-10 instances)**
   - Stateless Laravel applications
   - Auto-scaling based on CPU/memory
   - Session sharing via Redis

2. **Database Layer**
   - Primary PostgreSQL for writes
   - Read replicas for read-heavy queries
   - Connection pooling (PgBouncer)

3. **Redis Cluster**
   - DB 0: Default/Queue
   - DB 1: Cache
   - DB 2: Distributed Locks
   - DB 3: Sessions

4. **Load Balancer**
   - Nginx with least_conn algorithm
   - Health checks every 5 seconds
   - SSL/TLS termination

## Local Development Setup

### 1. Clone and Install

```bash
git clone https://github.com/kasunvimarshana/kv-saas-crm-erp.git
cd kv-saas-crm-erp

# Copy environment file
cp .env.example .env

# Install dependencies
composer install

# Generate application key
php artisan key:generate
```

### 2. Configure Environment

Edit `.env`:

```env
# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kv_saas_erp
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# API
API_RATE_LIMIT=60
```

### 3. Run Migrations

```bash
php artisan migrate --seed
```

### 4. Start Services

```bash
# Application server
php artisan serve

# Queue worker (separate terminal)
php artisan queue:work redis --queue=high,default,low
```

### 5. Test Health Endpoint

```bash
curl http://localhost:8000/api/health
```

Expected response:
```json
{
  "status": "healthy",
  "timestamp": "2026-02-10T01:00:00Z",
  "version": "1.0.0",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "distributed_locks": "ok",
    "queue": "ok"
  },
  "queue_depth": 0
}
```

## Production Deployment

### Option 1: Docker Compose (Simple Multi-Server)

#### 1. Create Docker Compose Configuration

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    image: kv-erp:latest
    deploy:
      replicas: 3
      update_config:
        parallelism: 1
        delay: 10s
      restart_policy:
        condition: on-failure
    environment:
      APP_ENV: production
      DB_HOST: postgres
      REDIS_HOST: redis
    depends_on:
      - postgres
      - redis
    networks:
      - backend

  queue:
    image: kv-erp:latest
    command: php artisan queue:work redis --queue=high,default,low --tries=3
    deploy:
      replicas: 2
    environment:
      APP_ENV: production
      REDIS_HOST: redis
    depends_on:
      - redis
    networks:
      - backend

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/load-balancer.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app
    networks:
      - backend

  postgres:
    image: postgres:14
    environment:
      POSTGRES_DB: kv_saas_erp
      POSTGRES_USER: app_user
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - backend

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    networks:
      - backend

networks:
  backend:

volumes:
  postgres_data:
  redis_data:
```

#### 2. Deploy

```bash
# Build image
docker build -t kv-erp:latest .

# Deploy stack
docker-compose -f docker-compose.prod.yml up -d

# Scale application servers
docker-compose -f docker-compose.prod.yml up -d --scale app=5
```

### Option 2: Kubernetes (Advanced)

See [Kubernetes Deployment](#kubernetes-deployment) section below.

## Kubernetes Deployment

### Prerequisites

- Kubernetes cluster (EKS, GKE, AKS, or on-premise)
- kubectl configured
- Persistent storage (EFS, GCS, Azure Files)
- PostgreSQL and Redis (managed services recommended)

### 1. Create Namespace

```bash
kubectl create namespace production
```

### 2. Create Secrets

```bash
# Create secrets
kubectl create secret generic kv-erp-secrets \
  --from-literal=app-key="base64:..." \
  --from-literal=db-username="app_user" \
  --from-literal=db-password="secure_password" \
  --from-literal=redis-password="redis_password" \
  -n production
```

### 3. Deploy Application

```bash
# Apply Kubernetes manifests
kubectl apply -f kubernetes/production-deployment.yaml -n production

# Verify deployment
kubectl get pods -n production
kubectl get svc -n production
kubectl get hpa -n production
```

### 4. Monitor Autoscaling

```bash
# Watch HPA status
kubectl get hpa -n production -w

# Check pod count
kubectl get pods -n production | grep kv-erp-api | wc -l
```

### 5. Load Testing (Trigger Autoscaling)

```bash
# Install Apache Bench
apt-get install apache2-utils

# Send 10,000 requests with 100 concurrent connections
ab -n 10000 -c 100 -H "Authorization: Bearer token" https://api.example.com/api/v1/customers

# Watch pods scale up
kubectl get pods -n production -w
```

## Configuration Details

### Environment Variables

#### Essential Production Variables

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...  # Generate with: php artisan key:generate --show
APP_URL=https://api.example.com

# Database (Primary)
DB_CONNECTION=pgsql
DB_HOST=primary.db.example.com
DB_PORT=5432
DB_DATABASE=kv_saas_erp
DB_USERNAME=app_user
DB_PASSWORD=secure_password

# Database (Read Replicas)
DB_READ_HOST_1=replica1.db.example.com
DB_READ_HOST_2=replica2.db.example.com

# Redis (Cluster or Single)
REDIS_HOST=redis.example.com
REDIS_PORT=6379
REDIS_PASSWORD=secure_redis_password
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
SESSION_HTTP_ONLY=true

# Rate Limiting
API_RATE_LIMIT=120

# Multi-Tenancy
TENANCY_ENABLED=true
```

### Redis Configuration

For production, use Redis Sentinel or Redis Cluster:

```bash
# Redis Sentinel (High Availability)
REDIS_SENTINEL_HOSTS=sentinel1:26379,sentinel2:26379,sentinel3:26379
REDIS_SENTINEL_SERVICE=mymaster

# Or Redis Cluster
REDIS_CLUSTER=redis
REDIS_CLUSTER_NODES=node1:6379,node2:6379,node3:6379
```

### Database Connection Pooling (PgBouncer)

```ini
# /etc/pgbouncer/pgbouncer.ini
[databases]
kv_saas_erp = host=primary.db.example.com port=5432 dbname=kv_saas_erp

[pgbouncer]
listen_addr = 0.0.0.0
listen_port = 6432
auth_type = md5
pool_mode = transaction
max_client_conn = 1000
default_pool_size = 25
```

Update Laravel config:
```env
DB_HOST=pgbouncer.example.com
DB_PORT=6432
```

## Monitoring & Operations

### Health Checks

```bash
# Check application health
curl https://api.example.com/api/health

# Expected response
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "distributed_locks": "ok",
    "queue": "ok"
  }
}
```

### Queue Monitoring

```bash
# Check queue depth
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Database Monitoring

```sql
-- Check connection count
SELECT count(*) FROM pg_stat_activity;

-- Check slow queries
SELECT pid, query, now() - query_start AS duration
FROM pg_stat_activity
WHERE state = 'active' AND now() - query_start > interval '5 seconds';

-- Check replication lag (on replica)
SELECT now() - pg_last_xact_replay_timestamp() AS replication_lag;
```

### Redis Monitoring

```bash
# Connect to Redis
redis-cli -h redis.example.com -p 6379 -a password

# Check memory usage
INFO memory

# Check connected clients
INFO clients

# Check keyspace
INFO keyspace

# Monitor commands in real-time
MONITOR
```

### Application Metrics

Monitor these key metrics:

1. **Request Rate**: Requests per second
2. **Response Time**: p50, p95, p99 latency
3. **Error Rate**: 4xx, 5xx errors
4. **Queue Depth**: Number of pending jobs
5. **Database Connections**: Active connections
6. **Redis Memory**: Used memory
7. **Pod Count**: Number of running instances

### Logging

Centralized logging with structured logs:

```php
// Laravel logging
Log::info('Order created', [
    'order_id' => $order->id,
    'tenant_id' => $order->tenant_id,
    'user_id' => auth()->id(),
    'timestamp' => now()->toIso8601String(),
]);
```

Ship logs to:
- ELK Stack (Elasticsearch, Logstash, Kibana)
- CloudWatch (AWS)
- Stackdriver (GCP)
- Azure Monitor (Azure)

## Troubleshooting

### Pod Not Starting

```bash
# Check pod status
kubectl describe pod <pod-name> -n production

# Check logs
kubectl logs <pod-name> -n production

# Check events
kubectl get events -n production --sort-by='.lastTimestamp'
```

### Database Connection Issues

```bash
# Test from pod
kubectl exec -it <pod-name> -n production -- bash
php artisan tinker
DB::connection()->getPdo();
```

### Redis Connection Issues

```bash
# Test from pod
kubectl exec -it <pod-name> -n production -- bash
redis-cli -h redis.production.svc.cluster.local ping
```

### High Response Times

1. Check database slow queries
2. Check Redis memory usage
3. Check queue depth
4. Check CPU/memory usage
5. Enable Laravel debugbar (dev only)

### Failed Jobs

```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry <job-id>

# Retry all failed
php artisan queue:retry all

# Delete failed job
php artisan queue:forget <job-id>
```

### Session Issues

Check Redis session store:
```bash
redis-cli -h redis.example.com -n 3 KEYS "session:*"
```

### Lock Timeout Issues

Check distributed locks:
```bash
redis-cli -h redis.example.com -n 2 KEYS "lock:*"

# Manually release stuck lock (use with caution)
redis-cli -h redis.example.com -n 2 DEL "lock:tenant:1:stock:product-123:warehouse-456"
```

## Performance Tuning

### Database Optimization

```sql
-- Create indexes for frequently queried columns
CREATE INDEX idx_orders_tenant_created ON orders (tenant_id, created_at);
CREATE INDEX idx_customers_tenant_status ON customers (tenant_id, status);

-- Analyze tables
ANALYZE orders;
ANALYZE customers;
```

### Redis Optimization

```bash
# Set max memory policy
redis-cli CONFIG SET maxmemory-policy allkeys-lru

# Set max memory
redis-cli CONFIG SET maxmemory 4gb
```

### Laravel Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

## Security Checklist

- [ ] Enable HTTPS/SSL
- [ ] Set `APP_DEBUG=false`
- [ ] Use strong database passwords
- [ ] Use Redis authentication
- [ ] Enable firewall rules
- [ ] Restrict pod security policies
- [ ] Enable RBAC in Kubernetes
- [ ] Regular security updates
- [ ] Enable audit logging
- [ ] Implement rate limiting
- [ ] Use secrets management (Vault, AWS Secrets Manager)

## Backup & Disaster Recovery

### Database Backups

```bash
# Full backup
pg_dump -h primary.db.example.com -U app_user kv_saas_erp > backup.sql

# Automated daily backups (cron)
0 2 * * * pg_dump -h primary.db.example.com -U app_user kv_saas_erp | gzip > /backups/db-$(date +\%Y\%m\%d).sql.gz
```

### Redis Backups

```bash
# Trigger manual save
redis-cli BGSAVE

# Copy RDB file
cp /var/lib/redis/dump.rdb /backups/redis-$(date +%Y%m%d).rdb
```

### Application State

```bash
# Backup storage directory
tar -czf storage-backup.tar.gz storage/

# Sync to S3
aws s3 sync storage/ s3://backups/storage/ --delete
```

## Scaling Guidelines

### When to Scale Up

- CPU usage > 70% sustained
- Memory usage > 80% sustained
- Response time p95 > 2 seconds
- Queue depth > 1000 jobs
- Error rate > 1%

### Horizontal Scaling

```bash
# Kubernetes (automatic)
# HPA will scale based on CPU/memory metrics

# Manual scaling
kubectl scale deployment kv-erp-api --replicas=5 -n production

# Docker Swarm
docker service scale kv-erp_app=5
```

### Vertical Scaling

```yaml
# Increase pod resources in Kubernetes manifest
resources:
  requests:
    memory: "512Mi"
    cpu: "500m"
  limits:
    memory: "1Gi"
    cpu: "1000m"
```

## Support

For issues or questions:
- GitHub Issues: https://github.com/kasunvimarshana/kv-saas-crm-erp/issues
- Documentation: See `DISTRIBUTED_SYSTEM_ARCHITECTURE.md`
- Architecture: See `ARCHITECTURE.md`

---

**Last Updated**: 2026-02-10  
**Version**: 1.0.0
