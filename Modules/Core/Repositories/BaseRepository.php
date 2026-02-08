<?php

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Base Repository Implementation
 * 
 * Provides a base implementation of the repository pattern for all entity repositories.
 * Implements common CRUD operations and query methods.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria): ?Model
    {
        return $this->model->where($criteria)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->findById($id);
        
        if (!$model) {
            throw new \Exception("Model with ID {$id} not found.");
        }
        
        $model->update($data);
        
        return $model->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $model = $this->findById($id);
        
        if (!$model) {
            return false;
        }
        
        return $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        return $this->model->where($criteria)->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findWherePaginated(array $criteria, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->where($criteria)->paginate($perPage, $columns);
    }
}
