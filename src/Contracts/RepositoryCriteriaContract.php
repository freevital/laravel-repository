<?php

namespace Freevital\Repository\Contracts;

use Illuminate\Support\Collection;

interface RepositoryCriteriaContract
{
    /**
     * Push Criteria to filter entities.
     *
     * @param $criteria
     *
     * @return $this
     * @throws RepositoryException
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
