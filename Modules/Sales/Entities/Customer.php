<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Sales\Database\Factories\CustomerFactory;

/**
 * Customer Entity
 *
 * Represents a customer in the sales system.
 * Supports multi-language names and tenant isolation.
 */
class Customer extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'customer_number',
        'name',
        'legal_name',
        'type',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_number',
        'currency',
        'payment_terms',
        'credit_limit',
        'status',
        'tags',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tags' => 'array',
        'credit_limit' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['name', 'notes'];

    /**
     * Get customer's sales orders.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Get customer's leads.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get customer's contacts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function contacts()
    {
        return $this->morphMany('App\Models\Contact', 'contactable');
    }

    /**
     * Get customer's addresses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function addresses()
    {
        return $this->morphMany('App\Models\Address', 'addressable');
    }

    /**
     * Check if customer is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer has credit available.
     */
    public function hasCreditAvailable(float $amount): bool
    {
        if (! $this->credit_limit) {
            return true;
        }

        $totalOutstanding = $this->salesOrders()
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('total_amount');

        return ($totalOutstanding + $amount) <= $this->credit_limit;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
