<?php

namespace Freevital\Repository\Contracts;

interface RepositoryContract
{
    /**
     * Retrieve all entities and build a paginator.
     *
     * @param integer|null $limit
     * @param array        $columns
     * @param string       $method
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate');

    /**
     * Retrieve all entities and build a simple paginator.
     *
     * @param integer|null $limit
     * @param array        $columns
     *
     * @return mixed
     */
    public function simplePaginate($limit = null, $columns = ['*']);

    /**
     * Retrieve all entities.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*']);

    /**
     * Retrieve the entities array for populate field select.
     *
     * @param string      $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function lists($column, $key = null);

    /**
     * Find an entity by id.
     *
     * @param int   $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*']);

    /**
     * Find a first entity.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*']);

    /**
     * Find the entities by field and value.
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $columns
     *
     * @return mixed
     */
    public function findByField($field, $value, $columns = ['*']);

    /**
     * Find the entities by multiple fields.
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*']);

    /**
     * Find the entities by multiple values in one field.
     *
     * @param mixed $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereIn($field, array $values, $columns = ['*']);

    /**
     * Find the entities by excluding multiple values in one field.
     *
     * @param mixed $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereNotIn($field, array $values, $columns = ['*']);

    /**
     * Count the entities.
     *
     * @return mixed
     */
    public function count();

    /**
     * Create a new entity.
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function create(array $attributes);

    /**
     * Update an entity by id.
     *
     * @param array $attributes
     * @param int   $id
     *
     * @return mixed
     */
    public function update(array $attributes, $id);

    /**
     * Update or Create an entity.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return mixed
     */
    public function updateOrCreate(array $attributes, array $values = []);

    /**
     * Update a status of an entity.
     *
     * @param bool $status
     * @param int  $id
     *
     * @return mixed
     */
    public function updateActiveStatus($status, int $id);

    /**
     * Delete an entity by id.
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id);

    /**
     * Force delete an entity by id.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function forceDelete($id);

    /**
     * Delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return int
     */
    public function deleteWhere(array $where);

    /**
     * Force delete multiple entities by given criteria.
     *
     * @param array $where
     *
     * @return int
     */
    public function forceDeleteWhere(array $where);

    /**
     * Check if entity has relation.
     *
     * @param string $relation
     *
     * @return $this
     */
    public function has($relation);

    /**
     * Load relations.
     *
     * @param array|string $relations
     *
     * @return $this
     */
    public function with($relations);

    /**
     * Load relation with closure.
     *
     * @param string  $relation
     * @param closure $closure
     *
     * @return $this
     */
    public function whereHas($relation, $closure);

    /**
     * Order the collection by a given column.
     *
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc');

    /**
     * Set visible fields.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function visible(array $fields);

    /**
     * Set hidden fields.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function hidden(array $fields);

    /**
     * Query Scope.
     *
     * @param \Closure $scope
     *
     * @return $this
     */
    public function scopeQuery(\Closure $scope);

    /**
     * Reset Query Scope.
     *
     * @return $this
     */
    public function resetScope();
}
