<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Payment;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Payment Repository Interface
 *
 * Defines the contract for payment data access operations.
 */
interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find payment by payment number.
     *
     * @param string $paymentNumber
     * @return Payment|null
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment;

    /**
     * Get payments by customer.
     *
     * @param int $customerId
     * @return Collection
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get payments by invoice.
     *
     * @param int $invoiceId
     * @return Collection
     */
    public function getByInvoice(int $invoiceId): Collection;

    /**
     * Get payments by status.
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get payments by date range.
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Collection
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get payments by payment method.
     *
     * @param string $method
     * @return Collection
     */
    public function getByPaymentMethod(string $method): Collection;
}
