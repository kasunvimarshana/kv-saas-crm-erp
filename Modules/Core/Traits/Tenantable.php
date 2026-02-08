<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tenantable Trait
 * 
 * Ensures all queries are scoped to the current tenant.
 * Implements multi-tenant data isolation at the model level.
 */
trait Tenantable
{
    /**
     * Boot the tenantable trait for a model.
     *
     * @return void
     */
    protected static function bootTenantable()
    {
        static::creating(function ($model) {
            if (!$model->tenant_id && tenancy()->initialized) {
                $model->tenant_id = tenancy()->tenant->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenancy()->initialized) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', tenancy()->tenant->id);
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }
}
