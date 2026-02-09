<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Account;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Account Repository Interface
 *
 * Defines the contract for account data access operations.
 */
interface AccountRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find account by account number.
     *
     * @param string $accountNumber
     * @return Account|null
     */
    public function findByAccountNumber(string $accountNumber): ?Account;

    /**
     * Get accounts by type.
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection;

    /**
     * Get active accounts.
     *
     * @return Collection
     */
    public function getActiveAccounts(): Collection;

    /**
     * Get chart of accounts in hierarchical structure.
     *
     * @return Collection
     */
    public function getChartOfAccounts(): Collection;

    /**
     * Get child accounts of a parent.
     *
     * @param int $parentId
     * @return Collection
     */
    public function getChildAccounts(int $parentId): Collection;

    /**
     * Search accounts by name or number.
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection;
}
