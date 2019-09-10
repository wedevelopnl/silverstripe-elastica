<?php

namespace TheWebmen\Elastica\Model;

class PaginatedList extends \SilverStripe\ORM\PaginatedList
{
    public function getTotalItems()
    {
        if ($this->list instanceof FacetIndexItemsList) {
            return $this->list->getTotalItems();
        }
        return parent::getTotalItems();
    }
}
