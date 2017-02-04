<?php

namespace Freevital\Repository\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Freevital\Repository\Contracts\CriteriaContract;
use Freevital\Repository\Contracts\RepositoryContract;
use Freevital\Repository\Contracts\RepositoryCriteriaContract;
use Freevital\Repository\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Container\Container as Application;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryContract, RepositoryCriteriaContract
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * Collection of Criteria
     *
     * @var Collection
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * @var \Closure
     */
    protected $scopeQuery = null;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
        $this->query = $this->model->newQuery();
        $this->criteria = new Collection();
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * @return Model
     * @throws RepositoryException
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());
        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * @return Builder
     */
    public function resetQuery()
    {
        return $this->query = $this->model->newQuery();
    }

    /**
     * Retrieve all entities and build a paginator.
     *
     * @param integer|null $limit
     * @param array        $columns
     * @param string       $method
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        $this->applyCriteria();
        $this->applyScope();

        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $result = $this->query->{$method}($limit, $columns);
        $result->appends(app('request')->query()); // TODO: need to refactor

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Retrieve all entities and build a simple paginator.
     *
     * @param integer|null $limit
     * @param array        $columns
     *
     * @return mixed
     */
    public function simplePaginate($limit = null, $columns = ['*'])
    {
        return $this->paginate($limit, $columns, 'simplePaginate');
    }

    /**
     * Retrieve all entities.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->query->get($columns);

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Retrieve the entities array for populate field select.
     *
     * @param string      $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function lists($column, $key = null)
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->query->lists($column, $key);

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Find an entity by id.
     *
     * @param int   $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query
            ->where($this->model->getQualifiedKeyName(), $id)
            ->firstOrFail($columns);

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Find a first entity.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query->firstOrFail($columns);

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Find the entities by field and value.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $columns
     *
     * @return mixed
     */
    public function findByField($field, $value, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->query->where($field, '=', $value)->get($columns);

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Find the entities by multiple fields.
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $this->applyConditions($where);

        $result = $this->query->get($columns);

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Find the entities by multiple values in one field.
     *
     * @param mixed $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereIn($field, array $values, $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->query->whereIn($field, $values)->get($columns);

        $this->resetQuery();

        return $result;
    }

    /**
     * Find the entities by excluding multiple values in one field.
     *
     * @param mixed $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereNotIn($field, array $values, $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->query->whereNotIn($field, $values)->get($columns);

        $this->resetQuery();

        return $result;
    }

    /**
     * Count the entities.
     *
     * @return mixed
     */
    public function count()
    {
        $this->applyCriteria();
        $this->applyScope();

        $count = $this->query->count();

        $this->resetScope();
        $this->resetQuery();

        return $count;
    }

    /**
     * Create a new entity.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function create(array $attributes)
    {
        $model = $this->model->newInstance();
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Update an entity by id.
     *
     * @param array $attributes
     * @param int   $id
     *
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query->findOrFail($id);
        $model->fill($attributes);
        $model->save();

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Update or Create an entity.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return mixed
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query->updateOrCreate($attributes, $values);

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Update a status of an entity.
     *
     * @param bool $status
     * @param int  $id
     *
     * @return mixed
     */
    public function updateActiveStatus($status, int $id)
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query
            ->where($this->model->getQualifiedKeyName(), $id)
            ->first();
        $model->is_active = $status;
        $model->save();

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Delete an entity by id.
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->find($id);
        $deleted = $model->delete();

        $this->resetScope();
        $this->resetQuery();

        return $deleted;
    }

    /**
     * Force delete an entity by id.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function forceDelete($id)
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->find($id);
        $deleted = $model->forceDelete();

        $this->resetScope();
        $this->resetQuery();

        return $deleted;
    }

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return int
     */
    public function deleteWhere(array $where)
    {
        $this->applyScope();
        $this->applyConditions($where);

        $deleted = $this->query->delete();

        $this->resetQuery();

        return $deleted;
    }

    /**
     * Force delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return int
     */
    public function forceDeleteWhere(array $where)
    {
        $this->applyScope();
        $this->applyConditions($where);

        $deleted = $this->query->forceDelete();

        $this->resetQuery();

        return $deleted;
    }

    /**
     * Check if entity has relation.
     *
     * @param string $relation
     *
     * @return $this
     */
    public function has($relation)
    {
        $this->query = $this->query->has($relation);

        return $this;
    }

    /**
     * Load relations.
     *
     * @param array|string $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        $this->query = $this->query->with($relations);

        return $this;
    }

    /**
     * Load relation with closure.
     *
     * @param string  $relation
     * @param closure $closure
     *
     * @return $this
     */
    public function whereHas($relation, $closure)
    {
        $this->query = $this->query->whereHas($relation, $closure);

        return $this;
    }

    /**
     * Order the collection by a given column.
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->query = $this->query->orderBy($column, $direction);

        return $this;
    }

    /**
     * Set visible fields.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function visible(array $fields)
    {
        $this->query->setVisible($fields);

        return $this;
    }

    /**
     * Set hidden fields.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function hidden(array $fields)
    {
        $this->query->setHidden($fields);

        return $this;
    }

    /**
     * Query Scope.
     *
     * @param \Closure $scope
     *
     * @return $this
     */
    public function scopeQuery(\Closure $scope)
    {
        $this->scopeQuery = $scope;

        return $this;
    }

    /**
     * Reset Query Scope.
     *
     * @return $this
     */
    public function resetScope()
    {
        $this->scopeQuery = null;

        return $this;
    }

    /**
     * Add query by 'is_active' attribute to query.
     *
     * @param bool $isActive
     *
     * @return BaseRepository
     */
    protected function applyActiveCondition($isActive = true)
    {
        return $this->scopeQuery(function ($query) use ($isActive) {
            return $query->where($query->getModel()->getTable() . '.is_active', $isActive);
        });
    }

    /**
     * Push Criteria for filter the query.
     *
     * @param $criteria
     *
     * @return $this
     * @throws RepositoryException
     */
    public function pushCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = new $criteria;
        }
        if (!$criteria instanceof CriteriaContract) {
            throw new RepositoryException("Class " . get_class($criteria) . " must be an instance of Prettus\\Repository\\Contracts\\CriteriaInterface");
        }
        $this->criteria->push($criteria);

        return $this;
    }

    /**
     * Pop Criteria.
     *
     * @param $criteria
     *
     * @return $this
     */
    public function popCriteria($criteria)
    {
        $this->criteria = $this->criteria->reject(function ($item) use ($criteria) {
            if (is_object($item) && is_string($criteria)) {
                return get_class($item) === $criteria;
            }
            if (is_string($item) && is_object($criteria)) {
                return $item === get_class($criteria);
            }

            return get_class($item) === get_class($criteria);
        });

        return $this;
    }

    /**
     * Get Collection of Criteria.
     *
     * @return Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Find data by Criteria.
     *
     * @param CriteriaContract $criteria
     *
     * @return mixed
     */
    public function getByCriteria(CriteriaContract $criteria)
    {
        $this->query = $criteria->apply($this->query, $this);
        $result = $this->query->get();
        $this->resetQuery();

        return $result;
    }

    /**
     * Skip Criteria.
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;

        return $this;
    }

    /**
     * Reset all Criteria.
     *
     * @return $this
     */
    public function resetCriteria()
    {
        $this->criteria = new Collection();

        return $this;
    }

    /**
     * Apply scope in current Query.
     *
     * @return $this
     */
    protected function applyScope()
    {
        if (isset($this->scopeQuery) && is_callable($this->scopeQuery)) {
            $callback = $this->scopeQuery;
            $this->query = $callback($this->query);
        }

        return $this;
    }

    /**
     * Apply criteria in current Query.
     *
     * @return $this
     */
    protected function applyCriteria()
    {
        if ($this->skipCriteria === true) {
            return $this;
        }
        $criteria = $this->getCriteria();
        if ($criteria) {
            foreach ($criteria as $c) {
                if ($c instanceof CriteriaContract) {
                    $this->query = $c->apply($this->query, $this);
                }
            }
        }

        return $this;
    }

    /**
     * Applies the given where conditions to the model.
     *
     * @param array $where
     *
     * @return void
     */
    protected function applyConditions(array $where)
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->query = $this->query->where($field, $condition, $val);
            } else {
                $this->query = $this->query->where($field, '=', $value);
            }
        }
    }

    /**
     * TODO: need to comment
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    protected function __call($name, $arguments)
    {
        if ($pos = strpos($name, 'Active')) {
            $method = substr($name, 0, $pos);

            $this->applyActiveCondition();

            return call_user_func_array(array($this, $method), $arguments);
        }
    }
}
