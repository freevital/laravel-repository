<?php

namespace Freevital\Repository\Contracts;

use Freevital\Repository\Exceptions\RepositoryException;
use Illuminate\Support\Collection;

interface RepositoryCriteriaContract
{
    /**
     * Push Criteria to filter entities.
     *
     * @param $criteria
     *
     * @throws RepositoryException
     *
     * @return $this
     */
    public function pushCriteria($criteria);

    /**
     * Pop Criteria.
     *
     * @param $criteria
     *
     * @return $this
     */
    public function popCriteria($criteria);

    /**
     * Get Criterias Collection.
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Get entities by Criteria.
     *
     * @param CriteriaContract $criteria
     *
     * @return mixed
     */
    public function getByCriteria(CriteriaContract $criteria);

    /**
     * Skip Criteria.
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCriteria($status = true);

    /**
     * Reset all Criterias.
     *
     * @return $this
     */
    public function resetCriteria();
}
