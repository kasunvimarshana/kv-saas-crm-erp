<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\InvoiceLine;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Invoice Line Repository Interface
 *
 * Defines the contract for invoice line data access operations.
 */
interface InvoiceLineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get lines by invoice.
     *
     * @param int $invoiceId
     * @return Collection
     */
    public function getByInvoice(int $invoiceId): Collection;

    /**
     * Get lines by product.
     *
     * @param int $productId
     * @return Collection
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get lines by account.
     *
     * @param int $accountId
     * @return Collection
     */
    public function getByAccount(int $accountId): Collection;
}
