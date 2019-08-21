<?php

namespace TheWebmen\Elastica\Traits;

use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Filters\Filter;

trait FilterFieldTrait
{
    private $filter;

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function getFilter()
    {
        return $this->filter;
    }
}
