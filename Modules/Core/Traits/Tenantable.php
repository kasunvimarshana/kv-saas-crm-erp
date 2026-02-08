<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenantable Trait
 *
 * Ensures all queries are scoped to the current tenant.
 * Implements multi-tenant data isolation at the model level using stancl/tenancy.
 *
 * This follows the multi-tenant architecture patterns from the Emmy Awards
 * case study and implements proper tenant isolation as analyzed in the
 * resource documentation.
 *
 * Usage:
 * 1. Add trait to your model: use Tenantable;
 * 2. Ensure your model's table has a tenant_id column
 * 3. The trait will automatically:
 *    - Set tenant_id on creation
 *    - Scope all queries to current tenant
 *    - Prevent cross-tenant data access
 *
 * Migration example:
 * $table->string('tenant_id')->index();
 * $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
 *
 * @link https://github.com/stancl/tenancy
 */
trait Tenantable
{
    /**
     * Boot the tenantable trait for a model.
     */
    protected static function bootTenantable(): void
    {
        static::creating(function ($model) {
            if (! $model->tenant_id && tenancy()->initialized) {
                $model->tenant_id = tenancy()->tenant->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenancy()->initialized) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', tenancy()->tenant->id);
            }
        });
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
}
