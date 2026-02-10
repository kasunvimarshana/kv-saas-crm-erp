# Authentication System Setup Guide

## Quick Start

### Backend Setup

1. **Install Dependencies**
```bash
composer install
```

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

Add JWT configuration to `.env`:
```env
JWT_SECRET=${APP_KEY}
JWT_ACCESS_TOKEN_TTL=60
JWT_REFRESH_TOKEN_TTL=7
```

3. **Run Migrations**
```bash
php artisan migrate
```

4. **Seed Database** (Optional)
```bash
php artisan db:seed
```

5. **Start Server**
```bash
php artisan serve
```

API will be available at `http://localhost:8000`

### Frontend Setup

1. **Install Dependencies**
```bash
npm install
```

2. **Start Development Server**
```bash
npm run dev
```

3. **Build for Production**
```bash
npm run build
```

## Testing Authentication

### Using cURL

#### Register User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "Password123!"
  }'
```

Save the `access_token` from response.

#### Get User Profile
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

#### Logout
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Using Postman

1. Import collection from `docs/postman/auth-collection.json`
2. Set environment variable `base_url` to `http://localhost:8000`
3. Run requests in order: Register → Login → Get Profile

## Authentication Flow

### Standard Login Flow

```
User → Login Form
  ↓
  Send credentials to /api/v1/auth/login
  ↓
  Server validates credentials
  ↓
  Generate JWT access & refresh tokens
  ↓
  Return tokens + user data
  ↓
  Store tokens in localStorage
  ↓
  Set Authorization header for future requests
  ↓
  Redirect to dashboard
```

### Token Refresh Flow

```
API Request → 401 Unauthorized
  ↓
  Check if refresh token exists
  ↓
  Send refresh token to /api/v1/auth/refresh
  ↓
  Server validates refresh token
  ↓
  Generate new access & refresh tokens
  ↓
  Update stored tokens
  ↓
  Retry original request with new token
```

### Logout Flow

```
User → Logout Button
  ↓
  Send request to /api/v1/auth/logout
  ↓
  Server blacklists current token
  ↓
  Clear localStorage
  ↓
  Redirect to login page
```

## Multi-Tenancy

### Login to Specific Tenant

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "Password123!",
    "tenant_id": 1
  }'
```

The `tenant_id` will be embedded in the JWT token and used for all subsequent requests.

### Switching Tenants

User must logout and login again with different `tenant_id`. Tenant context cannot be changed without re-authentication for security.

## Security

### Password Requirements

- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

### Rate Limiting

- Login: 5 attempts per 5 minutes per email
- Password Reset: 3 attempts per hour per email

### Token Security

- Access tokens expire after 60 minutes (configurable)
- Refresh tokens expire after 7 days (configurable)
- Tokens are blacklisted on logout
- All tokens revoked on password reset

## Permission System

### Check Permissions in Frontend

```vue
<template>
  <button v-if="hasPermission('sales.order.create')">
    Create Order
  </button>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'
const { hasPermission } = useAuth()
</script>
```

### Check Permissions in Backend

```php
// In controller
$this->authorize('create', Order::class);

// In code
if ($user->hasPermission('sales.order.create')) {
    // Allow action
}
```

## Troubleshooting

### Issue: "Token not provided"

**Cause**: Authorization header missing

**Solution**: Ensure frontend sends `Authorization: Bearer <token>` header

### Issue: "Invalid or expired token"

**Cause**: Token expired or blacklisted

**Solution**: Use refresh token to get new access token

### Issue: "Too many login attempts"

**Cause**: Rate limit exceeded

**Solution**: Wait 5 minutes and try again

### Issue: "CORS error"

**Cause**: Frontend domain not whitelisted

**Solution**: Add domain to CORS whitelist in `config/cors.php`

## Running Tests

```bash
# All authentication tests
php artisan test --testsuite=IAM

# Specific test file
php artisan test Modules/IAM/Tests/Feature/AuthApiTest.php

# With coverage
php artisan test --testsuite=IAM --coverage
```

## API Documentation

Full API documentation available at:
- Swagger UI: `http://localhost:8000/api/documentation`
- OpenAPI Spec: `docs/api/openapi.yaml`
- Authentication Guide: `docs/JWT_AUTHENTICATION.md`

## Production Deployment

### Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

JWT_SECRET=your-production-secret
JWT_ACCESS_TOKEN_TTL=60
JWT_REFRESH_TOKEN_TTL=7

DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host
```

### Security Checklist

- [ ] Use HTTPS only
- [ ] Set secure `APP_KEY` and `JWT_SECRET`
- [ ] Enable CSRF protection
- [ ] Configure CORS whitelist
- [ ] Set up rate limiting
- [ ] Enable Redis for caching
- [ ] Configure proper database credentials
- [ ] Set up SSL certificates
- [ ] Enable audit logging
- [ ] Configure backup strategy

### Performance Optimization

- Use Redis for cache and sessions
- Enable OPcache
- Configure queue workers
- Set up CDN for static assets
- Enable response caching
- Optimize database queries
- Configure proper indexes

## Support

- Documentation: `/docs`
- Issue Tracker: GitHub Issues
- Email: support@example.com

---

**Version**: 1.0.0
**Last Updated**: 2024-02-10
