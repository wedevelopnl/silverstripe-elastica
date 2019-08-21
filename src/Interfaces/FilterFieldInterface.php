<?php

namespace TheWebmen\Elastica\Interfaces;

use TheWebmen\Elastica\Filters\Filter;

interface FilterFieldInterface
{
    /**
     * @return Filter
     */
    public function getFilter();

    /**
     * @param Filter $filter
     */
    public function setFilter(Filter $filter);
}
