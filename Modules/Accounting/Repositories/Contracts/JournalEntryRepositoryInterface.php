<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Journal Entry Repository Interface
 *
 * Defines the contract for journal entry data access operations.
 */
interface JournalEntryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find journal entry by entry number.
     *
     * @param string $entryNumber
     * @return JournalEntry|null
     */
    public function findByEntryNumber(string $entryNumber): ?JournalEntry;

    /**
     * Get entries by status.
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get entries by fiscal period.
     *
     * @param int $fiscalPeriodId
     * @return Collection
     */
    public function getByFiscalPeriod(int $fiscalPeriodId): Collection;

    /**
     * Get entries by date range.
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Collection
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get unbalanced entries.
     *
     * @return Collection
     */
    public function getUnbalancedEntries(): Collection;
}
