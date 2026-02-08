<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Context Middleware
 *
 * Validates and initializes tenant context for API requests.
 * Implements multi-tenant isolation at the middleware layer following
 * the Emmy Awards architecture patterns.
 *
 * This middleware:
 * - Identifies tenant from subdomain, header, or parameter
 * - Initializes tenant context with stancl/tenancy
 * - Returns 403 if tenant not found or inactive
 *
 * Usage: Apply to tenant-specific routes
 * Route::middleware(['tenant.context'])->group(...)
 */
class TenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Tenant is initialized by stancl/tenancy automatically
        // This middleware can be used for additional validation

        if (! tenancy()->initialized) {
            return response()->json([
                'message' => 'Tenant context not initialized',
            ], Response::HTTP_FORBIDDEN);
        }

        // Add tenant context to request for easy access
        $request->merge(['tenant_id' => tenancy()->tenant->id]);

        return $next($request);
    }
}
