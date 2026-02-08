<?php

namespace Modules\Sales\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Entities\Customer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Repository Implementation
 * 
 * Handles all customer data access operations.
 */
class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    /**
     * CustomerRepository constructor.
     *
     * @param Customer $model
     */
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByCustomerNumber(string $customerNumber): ?Customer
    {
        return $this->model->where('customer_number', $customerNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveCustomers(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('customer_number', 'LIKE', "%{$query}%")
            ->get();
    }
}
