<?php

namespace Modules\Core\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 * 
 * Defines the contract for all repository implementations following the Repository Pattern.
 * This ensures consistency across all data access layers in the application.
 */
interface BaseRepositoryInterface
{
    /**
     * Find a model by its primary key.
     *
     * @param int $id
     * @return Model|null
     */
    public function findById(int $id): ?Model;

    /**
     * Find a model by specific criteria.
     *
     * @param array $criteria
     * @return Model|null
     */
    public function findBy(array $criteria): ?Model;

    /**
     * Get all models.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated models.
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Create a new model.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a model.
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a model.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find models matching criteria.
     *
     * @param array $criteria
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find models matching criteria with pagination.
     *
     * @param array $criteria
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function findWherePaginated(array $criteria, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;
}
