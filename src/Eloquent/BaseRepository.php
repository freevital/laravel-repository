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
     * @var array
     */
    protected static $macros;

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
     * Paginate all entities.
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
        $result->appends(app('request')->query());

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Paginate all entities using simple paginator.
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
     * Get entities values of a given key.
     *
     * @param string      $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function pluck($column, $key = null)
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->query->pluck($column, $key);

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
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query
            ->where($this->model->getQualifiedKeyName(), $id)
            ->first($columns);

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Get first entity.
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public function first($columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->query->first($columns);

        $this->resetScope();
        $this->resetQuery();

        return $model;
    }

    /**
     * Find the entities by attribute and value.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $columns
     *
     * @return mixed
     */
    public function findByAttribute($attribute, $value, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->query->where($attribute, '=', $value)->get($columns);

        $this->resetScope();
        $this->resetQuery();

        return $result;
    }

    /**
     * Find the entities by multiple attributes.
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
     * Find the entities by multiple values in one attribute.
     *
     * @param mixed $attribute
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereIn($attribute, array $values, $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->query->whereIn($attribute, $values)->get($columns);

        $this->resetQuery();

        return $result;
    }

    /**
     * Find the entities by excluding multiple values in one attribute.
     *
     * @param mixed $attribute
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereNotIn($attribute, array $values, $columns = ['*'])
    {
        $this->applyCriteria();

        $result = $this->query->whereNotIn($attribute, $values)->get($columns);

        $this->resetQuery();

        return $result;
    }

    /**
     * Get entities count.
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
     * Create new entity.
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
     * Delete multiple entities by attribute values.
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
     * Force delete multiple entities by attribute values.
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
     * @param string   $relation
     * @param \Closure $closure
     *
     * @return $this
     */
    public function whereHas($relation, \Closure $closure)
    {
        $this->query = $this->query->whereHas($relation, $closure);

        return $this;
    }

    /**
     * Order the collection by a given attribute.
     *
     * @param string $attribute
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($attribute, $direction = 'asc')
    {
        $this->query = $this->query->orderBy($attribute, $direction);

        return $this;
    }

    /**
     * Set visible attributes.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function visible(array $attributes)
    {
        $this->query->setVisible($attributes);

        return $this;
    }

    /**
     * Set hidden attributes.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function hidden(array $attributes)
    {
        $this->query->setHidden($attributes);

        return $this;
    }

    /**
     * Additional Query Scope.
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
     * Push Criteria to filter entities.
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
     * Get Criterias Collection.
     *
     * @return Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Get entities by Criteria.
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
     * Reset all Criterias.
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
        foreach ($where as $attribute => $value) {
            if (is_array($value)) {
                list($attribute, $condition, $val) = $value;
                $this->query = $this->query->where($attribute, $condition, $val);
            } else {
                $this->query = $this->query->where($attribute, '=', $value);
            }
        }
    }

    /**
     * Register a new repository macros.
     *
     * @param string   $name
     * @param \Closure $callback
     *
     * @throws RepositoryException
     *
     * @return void
     */
    public static function macro($name, \Closure $callback)
    {
        if (!($callback instanceof \Closure)) {
            throw new RepositoryException("Class \"{$callback}\" must be an instance of Closure.");
        }

        static::$macros[$name] = $callback;
    }

    /**
     * Call registered repository marcos.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws RepositoryException
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (!isset(static::$macros[$name])) {
            throw new RepositoryException("Method or macros \"{$name}\" does not exists.");
        }

        call_user_func_array(static::$macros[$name], [$this, $arguments]);

        return $this;
    }
}
