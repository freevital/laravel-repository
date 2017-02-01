<?php

namespace Freevital\Repository\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface CriteriaContract
{
    /**
     * Apply criteria in query repository.
     *
     * @param Builder            $query
     * @param RepositoryContract $repository
     *
     * @return mixed
     */
    public function apply(Builder $query, RepositoryContract $repository);
}
