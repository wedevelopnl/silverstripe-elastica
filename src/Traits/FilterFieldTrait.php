<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Traits;

use WeDevelop\Elastica\Filters\Filter;

trait FilterFieldTrait
{
    private Filter $filter;

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function setFilter(Filter $filter): void
    {
        $this->filter = $filter;
    }
}
