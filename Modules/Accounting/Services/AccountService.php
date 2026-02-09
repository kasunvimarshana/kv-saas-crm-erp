<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Entities\Account;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Core\Services\BaseService;

/**
 * Account Service
 *
 * Handles business logic for chart of accounts management.
 */
class AccountService extends BaseService
{
    /**
     * AccountService constructor.
     *
     * @param AccountRepositoryInterface $accountRepository
     */
    public function __construct(
        protected AccountRepositoryInterface $accountRepository
    ) {}

    /**
     * Get paginated accounts.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->accountRepository->paginate($perPage);
    }

    /**
     * Create a new account.
     *
     * @param array $data
     * @return Account
     */
    public function create(array $data): Account
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate account number if not provided
            if (empty($data['account_number'])) {
                $data['account_number'] = $this->generateAccountNumber($data['type']);
            }

            // Set default values
            $data['is_active'] = $data['is_active'] ?? true;
            $data['allow_manual_entries'] = $data['allow_manual_entries'] ?? true;
            $data['balance'] = $data['balance'] ?? 0;

            $account = $this->accountRepository->create($data);

            $this->logInfo('Account created', [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
            ]);

            return $account;
        });
    }

    /**
     * Update an existing account.
     *
     * @param int $id
     * @param array $data
     * @return Account
     */
    public function update(int $id, array $data): Account
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $account = $this->accountRepository->update($id, $data);

            $this->logInfo('Account updated', [
                'account_id' => $account->id,
            ]);

            return $account;
        });
    }

    /**
     * Delete an account.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $account = $this->accountRepository->findById($id);

        if (!$account) {
            throw new \Exception('Account not found');
        }

        if ($account->isSystem()) {
            throw new \Exception('Cannot delete system account');
        }

        if ($account->journalEntryLines()->exists()) {
            throw new \Exception('Cannot delete account with journal entries');
        }

        if ($account->children()->exists()) {
            throw new \Exception('Cannot delete account with child accounts');
        }

        $result = $this->accountRepository->delete($id);

        if ($result) {
            $this->logInfo('Account deleted', [
                'account_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Find account by ID.
     *
     * @param int $id
     * @return Account|null
     */
    public function findById(int $id): ?Account
    {
        return $this->accountRepository->findById($id);
    }

    /**
     * Find account by account number.
     *
     * @param string $accountNumber
     * @return Account|null
     */
    public function findByAccountNumber(string $accountNumber): ?Account
    {
        return $this->accountRepository->findByAccountNumber($accountNumber);
    }

    /**
     * Get accounts by type.
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->accountRepository->getByType($type);
    }

    /**
     * Get active accounts.
     *
     * @return Collection
     */
    public function getActiveAccounts(): Collection
    {
        return $this->accountRepository->getActiveAccounts();
    }

    /**
     * Get chart of accounts.
     *
     * @return Collection
     */
    public function getChartOfAccounts(): Collection
    {
        return $this->accountRepository->getChartOfAccounts();
    }

    /**
     * Search accounts.
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection
    {
        return $this->accountRepository->search($query);
    }

    /**
     * Update account balance.
     *
     * @param int $accountId
     * @param float $amount
     * @param bool $isDebit
     * @return void
     */
    public function updateBalance(int $accountId, float $amount, bool $isDebit): void
    {
        $account = $this->accountRepository->findById($accountId);
        if ($account) {
            $account->updateBalance($amount, $isDebit);
        }
    }

    /**
     * Generate a unique account number.
     *
     * @param string $type
     * @return string
     */
    protected function generateAccountNumber(string $type): string
    {
        // Account number prefixes by type
        $prefixes = [
            Account::TYPE_ASSET => '1',
            Account::TYPE_LIABILITY => '2',
            Account::TYPE_EQUITY => '3',
            Account::TYPE_REVENUE => '4',
            Account::TYPE_EXPENSE => '5',
        ];

        $prefix = $prefixes[$type] ?? '9';

        // Get the last account number for this type
        $lastAccount = $this->accountRepository
            ->getModel()
            ->where('account_number', 'LIKE', "{$prefix}%")
            ->orderBy('account_number', 'desc')
            ->first();

        if ($lastAccount) {
            // Extract the sequence number and increment
            $sequence = (int) substr($lastAccount->account_number, 1) + 1;
        } else {
            $sequence = 1000;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
