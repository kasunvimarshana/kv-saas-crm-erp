<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\FiscalPeriod;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Fiscal Period Repository Interface
 *
 * Defines the contract for fiscal period data access operations.
 */
interface FiscalPeriodRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get periods by fiscal year.
     *
     * @param int $fiscalYear
     * @return Collection
     */
    public function getByFiscalYear(int $fiscalYear): Collection;

    /**
     * Get periods by status.
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get open periods.
     *
     * @return Collection
     */
    public function getOpenPeriods(): Collection;

    /**
     * Find period by date.
     *
     * @param \DateTimeInterface $date
     * @return FiscalPeriod|null
     */
    public function findByDate(\DateTimeInterface $date): ?FiscalPeriod;

    /**
     * Get current period.
     *
     * @return FiscalPeriod|null
     */
    public function getCurrentPeriod(): ?FiscalPeriod;
}
