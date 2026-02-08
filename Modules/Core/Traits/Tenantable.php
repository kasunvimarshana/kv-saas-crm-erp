<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Session;

/**
 * Tenantable Trait
 *
 * Ensures all queries are scoped to the current tenant using native Laravel features.
 * Implements multi-tenant data isolation at the model level without external packages.
 *
 * This follows the multi-tenant architecture patterns from the Emmy Awards
 * case study and implements proper tenant isolation using native Laravel scopes.
 *
 * Usage:
 * 1. Add trait to your model: use Tenantable;
 * 2. Ensure your model's table has a tenant_id column
 * 3. Set tenant in session: Session::put('tenant_id', $tenantId);
 * 4. The trait will automatically:
 *    - Set tenant_id on creation
 *    - Scope all queries to current tenant
 *    - Prevent cross-tenant data access
 *
 * Migration example:
 * $table->unsignedBigInteger('tenant_id')->index();
 * $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
 */
trait Tenantable
{
    /**
     * Boot the tenantable trait for a model.
     */
    protected static function bootTenantable(): void
    {
        // Automatically set tenant_id on creation
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $tenantId = static::getCurrentTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        // Add global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::getCurrentTenantId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });
    }

    /**
     * Get the current tenant ID from session or auth user.
     *
     * @return int|string|null
     */
    protected static function getCurrentTenantId(): int|string|null
    {
        // Try to get from session first
        if (Session::has('tenant_id')) {
            return Session::get('tenant_id');
        }

        // Try to get from authenticated user
        if (auth()->check() && method_exists(auth()->user(), 'getCurrentTenantId')) {
            return auth()->user()->getCurrentTenantId();
        }

        // Try to get from config (for testing/seeding)
        if (config('app.current_tenant_id')) {
            return config('app.current_tenant_id');
        }

        return null;
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model', 'App\Models\Tenant'), 'tenant_id');
    }

    /**
     * Scope a query to exclude tenant filtering.
     * Use with caution - only in admin contexts.
     */
    public function scopeWithoutTenancy(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope a query to a specific tenant.
     */
    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where($query->getModel()->getTable().'.tenant_id', $tenantId);
    }
}
