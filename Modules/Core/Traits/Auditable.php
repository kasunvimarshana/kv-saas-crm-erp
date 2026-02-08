<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Auditable Trait
 * 
 * Automatically tracks who created and updated records.
 * Provides audit trail functionality for all models.
 */
trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     *
     * @return void
     */
    protected static function bootAuditable()
    {
        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created the record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated the record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }
}
