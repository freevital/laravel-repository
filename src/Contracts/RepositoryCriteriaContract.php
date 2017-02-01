<?php

namespace Freevital\Repository\Contracts;

use Illuminate\Support\Collection;

interface RepositoryCriteriaContract
{
    /**
     * Push Criteria for filter the query.
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
     * Get Collection of Criteria.
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Find data by Criteria.
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
     * Reset all Criteria.
     *
     * @return $this
     */
    public function resetCriteria();
}
