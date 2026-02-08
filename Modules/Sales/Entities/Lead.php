<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\{Translatable, Tenantable, Auditable};

/**
 * Lead Entity
 * 
 * Represents a potential customer or sales opportunity.
 * Part of the CRM pipeline for converting leads to customers.
 */
class Lead extends Model
{
    use HasFactory, SoftDeletes, Translatable, Tenantable, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'lead_number',
        'customer_id',
        'source',
        'title',
        'description',
        'contact_name',
        'contact_email',
        'contact_phone',
        'company',
        'status',
        'stage',
        'probability',
        'expected_revenue',
        'expected_close_date',
        'assigned_to',
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
        'probability' => 'integer',
        'expected_revenue' => 'decimal:2',
        'expected_close_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['title', 'description', 'notes'];

    /**
     * Get the customer associated with the lead.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user assigned to the lead.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee()
    {
        return $this->belongsTo('App\Models\User', 'assigned_to');
    }

    /**
     * Get lead activities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activities()
    {
        return $this->morphMany('App\Models\Activity', 'subject');
    }

    /**
     * Convert lead to customer.
     *
     * @param array $customerData
     * @return Customer
     */
    public function convertToCustomer(array $customerData): Customer
    {
        $customer = Customer::create(array_merge($customerData, [
            'tenant_id' => $this->tenant_id,
        ]));

        $this->update([
            'customer_id' => $customer->id,
            'status' => 'converted',
        ]);

        return $customer;
    }

    /**
     * Check if lead is qualified.
     *
     * @return bool
     */
    public function isQualified(): bool
    {
        return $this->stage === 'qualified';
    }

    /**
     * Check if lead is won.
     *
     * @return bool
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }
}
