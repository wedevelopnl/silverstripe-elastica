<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Traits;

use TheWebmen\Elastica\Filters\Filter;

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
