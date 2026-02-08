<?php

namespace Modules\Sales\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Sales\Entities\Customer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer Repository Interface
 * 
 * Defines the contract for customer data access operations.
 */
interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find customer by email.
     *
     * @param string $email
     * @return Customer|null
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Find customer by customer number.
     *
     * @param string $customerNumber
     * @return Customer|null
     */
    public function findByCustomerNumber(string $customerNumber): ?Customer;

    /**
     * Get active customers.
     *
     * @return Collection
     */
    public function getActiveCustomers(): Collection;

    /**
     * Search customers by name or email.
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection;
}
