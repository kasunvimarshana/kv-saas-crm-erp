# JWT Authentication System Documentation

## Overview

This document provides comprehensive documentation for the JWT-based, fully stateless authentication system implemented in the kv-saas-crm-erp application.

## Architecture

### Principles

1. **Stateless Authentication**: No server-side session storage. All authentication state is contained in JWT tokens.
2. **Multi-Tenancy**: Tenant context embedded in JWT payload for automatic tenant isolation.
3. **RBAC/ABAC**: Role-Based and Attribute-Based Access Control integrated with authentication.
4. **Native Implementation**: Built entirely with native Laravel and Vue features - zero external auth packages.
5. **Security-First**: Rate limiting, token blacklisting, strong password requirements, and automatic token refresh.

### Components

#### Backend (Laravel)

```
Modules/IAM/
├── Services/
│   ├── JwtService.php          # Native JWT token generation/validation
│   └── AuthService.php         # Authentication business logic
├── Http/
│   ├── Controllers/
│   │   └── AuthController.php  # Auth API endpoints
│   ├── Middleware/
│   │   └── JwtAuthenticate.php # JWT authentication middleware
│   └── Requests/
│       ├── LoginRequest.php
│       ├── RegisterRequest.php
│       └── PasswordReset*.php
├── Events/
│   ├── UserLoggedIn.php
│   ├── UserLoggedOut.php
│   └── UserRegistered.php
└── Tests/
    ├── Unit/JwtServiceTest.php
    └── Feature/AuthApiTest.php
```

#### Frontend (Vue 3)

```
resources/js/
├── composables/
│   └── useAuth.js              # Authentication composable
└── components/
    └── Login.vue               # Login component
```

## JWT Token Structure

### Access Token Payload

```json
{
  "iss": "https://app.example.com",    // Issuer
  "sub": 123,                           // Subject (user ID)
  "iat": 1707552000,                    // Issued at (Unix timestamp)
  "exp": 1707555600,                    // Expiration (Unix timestamp)
  "tenant_id": 1,                       // Tenant ID
  "email": "user@example.com",          // User email
  "jti": "uuid-here"                    // JWT ID (unique identifier)
}
```

### Refresh Token Payload

```json
{
  "iss": "https://app.example.com",
  "sub": 123,
  "iat": 1707552000,
  "exp": 1708156800,                    // 7 days expiration
  "type": "refresh",
  "jti": "uuid-here"
}
```

### Token Signature

Tokens are signed using HS256 (HMAC-SHA256) algorithm with the application secret key:

```
signature = HMAC-SHA256(
  base64UrlEncode(header) + "." + base64UrlEncode(payload),
  secret
)
```

## API Endpoints

### Public Endpoints

#### POST /api/v1/auth/login

Authenticate user and generate JWT tokens.

**Request**:
```json
{
  "email": "user@example.com",
  "password": "Password123!",
  "tenant_id": 1  // Optional
}
```

**Response (200)**:
```json
{
  "message": "Login successful",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "user@example.com",
    "tenant_id": 1
  },
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "tenant_id": 1
}
```

**Error Responses**:
- `401`: Invalid credentials or inactive account
- `422`: Validation errors
- `429`: Too many login attempts (rate limited)

#### POST /api/v1/auth/register

Register new user account.

**Request**:
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "tenant_id": 1,  // Optional
  "role_id": 2     // Optional
}
```

**Response (201)**: Same as login response

**Error Responses**:
- `422`: Validation errors (weak password, duplicate email, etc.)
- `500`: Registration failed

#### POST /api/v1/auth/refresh

Refresh access token using refresh token.

**Request**:
```json
{
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200)**:
```json
{
  "message": "Token refreshed successfully",
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "tenant_id": 1
}
```

**Error Responses**:
- `401`: Invalid or expired refresh token

#### POST /api/v1/auth/password/reset

Initiate password reset process.

**Request**:
```json
{
  "email": "user@example.com"
}
```

**Response (200)**:
```json
{
  "message": "If your email exists in our system, you will receive a password reset link."
}
```

#### POST /api/v1/auth/password/reset/confirm

Reset password using reset token.

**Request**:
```json
{
  "email": "user@example.com",
  "token": "reset-token-here",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Response (200)**:
```json
{
  "message": "Password reset successful. Please login with your new password."
}
```

**Error Responses**:
- `400`: Invalid or expired token

### Protected Endpoints

All protected endpoints require `Authorization: Bearer <token>` header.

#### POST /api/v1/auth/logout

Logout from current session.

**Response (200)**:
```json
{
  "message": "Logout successful"
}
```

#### POST /api/v1/auth/logout-all

Logout from all devices.

**Response (200)**:
```json
{
  "message": "Logged out from all devices successfully"
}
```

#### GET /api/v1/auth/me

Get authenticated user profile.

**Response (200)**:
```json
{
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "user@example.com",
    "tenant_id": 1,
    "is_active": true,
    "permissions": ["sales.customer.view", "sales.order.create"],
    "roles": [
      {
        "id": 2,
        "name": "Sales Manager"
      }
    ]
  }
}
```

## Frontend Usage

### Using the Auth Composable

```vue
<script setup>
import { useAuth } from '@/composables/useAuth'

const { 
  user, 
  isAuthenticated, 
  isLoading, 
  error,
  login, 
  logout,
  hasPermission 
} = useAuth()

const handleLogin = async () => {
  try {
    await login({
      email: 'user@example.com',
      password: 'Password123!'
    })
    // Redirect to dashboard
  } catch (e) {
    console.error('Login failed:', e)
  }
}
</script>

<template>
  <div v-if="isAuthenticated">
    <p>Welcome, {{ user.name }}!</p>
    <button @click="logout">Logout</button>
  </div>
  <div v-else>
    <button @click="handleLogin">Login</button>
  </div>
</template>
```

### Permission-Based UI Rendering

```vue
<template>
  <div>
    <!-- Show button only if user has permission -->
    <button v-if="hasPermission('sales.order.create')">
      Create Order
    </button>
    
    <!-- Show if user has any of the permissions -->
    <div v-if="hasAnyPermission(['sales.order.view', 'sales.order.create'])">
      Order Management
    </div>
    
    <!-- Show if user has all permissions -->
    <div v-if="hasAllPermissions(['sales.order.view', 'sales.order.update'])">
      Edit Order
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '@/composables/useAuth'

const { hasPermission, hasAnyPermission, hasAllPermissions } = useAuth()
</script>
```

### Route Guards

```javascript
// router/index.js
import { useAuth } from '@/composables/useAuth'

router.beforeEach((to, from, next) => {
  const { isAuthenticated } = useAuth()
  
  if (to.meta.requiresAuth && !isAuthenticated.value) {
    next('/login')
  } else {
    next()
  }
})
```

## Multi-Tenancy

### How It Works

1. **Token Generation**: Tenant ID is embedded in JWT payload during login
2. **Token Validation**: Middleware extracts tenant ID from token and sets context
3. **Automatic Scoping**: All database queries automatically filtered by tenant
4. **Session Storage**: Tenant ID stored in session for compatibility with legacy code

### Tenant Context Resolution

Priority order:
1. JWT token payload (`tenant_id` claim)
2. Session storage (`session('tenant_id')`)
3. Authenticated user (`auth()->user()->tenant_id`)
4. Configuration fallback (`config('app.current_tenant_id')`)

### Example: Multi-Tenant Login

```javascript
// Login to specific tenant
await login({
  email: 'user@example.com',
  password: 'Password123!',
  tenant_id: 2  // Login to tenant 2
})

// User can only access tenant 2 data
// All queries automatically scoped to tenant 2
```

## Security Features

### Rate Limiting

Login attempts are rate-limited to prevent brute force attacks:
- **Limit**: 5 attempts per 5 minutes per email address
- **Reset**: Counter reset on successful login
- **Response**: 422 error with retry time

### Password Requirements

Strong password policy enforced:
- Minimum 8 characters
- Must contain uppercase letter
- Must contain lowercase letter
- Must contain number
- Must contain symbol

### Token Blacklisting

Tokens can be revoked:
- **Single Logout**: Blacklists current access token
- **Logout All**: Blacklists all user tokens
- **Password Reset**: Revokes all existing tokens
- **Storage**: Redis cache with TTL matching token expiry

### Token Expiry

- **Access Token**: 60 minutes (configurable via `JWT_ACCESS_TOKEN_TTL`)
- **Refresh Token**: 7 days (configurable via `JWT_REFRESH_TOKEN_TTL`)
- **Automatic Refresh**: Frontend auto-refreshes on 401 response

## Configuration

### Environment Variables

```env
# JWT Configuration
JWT_SECRET=your-secret-key-here
JWT_ACCESS_TOKEN_TTL=60       # minutes
JWT_REFRESH_TOKEN_TTL=7       # days
```

### IAM Module Config

```php
// Modules/IAM/Config/config.php
'jwt' => [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'access_token_ttl' => env('JWT_ACCESS_TOKEN_TTL', 60),
    'refresh_token_ttl' => env('JWT_REFRESH_TOKEN_TTL', 7),
    'algorithm' => 'HS256',
    'token_type' => 'Bearer',
],
```

## Testing

### Running Tests

```bash
# Run all IAM tests
php artisan test --testsuite=IAM

# Run authentication tests only
php artisan test Modules/IAM/Tests/Feature/AuthApiTest.php

# Run JWT service tests
php artisan test Modules/IAM/Tests/Unit/JwtServiceTest.php

# Run with coverage
php artisan test --testsuite=IAM --coverage
```

### Test Coverage

- **Unit Tests**: 7 test cases covering JwtService
- **Feature Tests**: 11 test cases covering Auth API endpoints
- **Total Coverage**: 18 test cases

## Troubleshooting

### Token Expired Error

**Symptom**: 401 Unauthenticated with "Invalid or expired token"

**Solution**:
1. Frontend auto-refresh should handle this
2. If refresh token also expired, user must login again
3. Check token TTL configuration

### Rate Limit Errors

**Symptom**: 422 error with "Too many login attempts"

**Solution**:
1. Wait for the time specified in error message
2. Check if email/IP is being targeted by brute force
3. Implement CAPTCHA for additional security

### Multi-Tenant Data Leakage

**Symptom**: User seeing data from other tenants

**Solution**:
1. Verify tenant_id in JWT payload
2. Check Tenantable trait is applied to models
3. Ensure global scopes are not bypassed
4. Verify middleware is registered correctly

### CORS Errors

**Symptom**: Browser blocking API requests

**Solution**:
1. Add frontend domain to CORS whitelist
2. Ensure credentials are included in requests
3. Check CORS middleware configuration

## Best Practices

### Security

1. **Never** expose JWT secret in client-side code
2. **Always** use HTTPS in production
3. **Implement** CAPTCHA after multiple failed attempts
4. **Rotate** JWT secret periodically
5. **Monitor** for suspicious login patterns
6. **Limit** token lifetime to minimum required
7. **Validate** all user input on server-side

### Performance

1. **Cache** user permissions for frequent checks
2. **Use** Redis for token blacklist storage
3. **Minimize** token payload size
4. **Implement** token refresh before expiry
5. **Avoid** database queries in middleware when possible

### Maintainability

1. **Keep** authentication logic in AuthService
2. **Use** events for cross-cutting concerns
3. **Write** comprehensive tests for all auth flows
4. **Document** any custom authentication logic
5. **Version** API endpoints for backward compatibility

## Migration from Sanctum

If migrating from Laravel Sanctum:

1. Install new JWT system (already done)
2. Update API routes to use `jwt.auth` middleware
3. Update frontend to use useAuth composable
4. Test all authentication flows
5. Deploy with feature flag
6. Monitor for issues
7. Remove Sanctum after validation

## Support

For issues or questions:
- Check documentation: `ARCHITECTURE.md`, `NATIVE_FEATURES.md`
- Review tests for usage examples
- File issues on GitHub
- Contact development team

---

**Last Updated**: 2024-02-10
**Version**: 1.0.0
**Maintainer**: Development Team
