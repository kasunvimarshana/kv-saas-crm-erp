<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Invoice;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Invoice Repository Interface
 *
 * Defines the contract for invoice data access operations.
 */
interface InvoiceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find invoice by invoice number.
     *
     * @param string $invoiceNumber
     * @return Invoice|null
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice;

    /**
     * Get invoices by customer.
     *
     * @param int $customerId
     * @return Collection
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get invoices by status.
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get overdue invoices.
     *
     * @return Collection
     */
    public function getOverdueInvoices(): Collection;

    /**
     * Get unpaid invoices.
     *
     * @return Collection
     */
    public function getUnpaidInvoices(): Collection;

    /**
     * Get invoices by date range.
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Collection
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get aging report data.
     *
     * @return Collection
     */
    public function getAgingReport(): Collection;
}
