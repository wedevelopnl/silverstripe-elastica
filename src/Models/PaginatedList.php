<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Model;

final class PaginatedList extends \SilverStripe\ORM\PaginatedList
{
    public function getTotalItems(): int
    {
        if ($this->list instanceof FacetIndexItemsList) {
            return $this->list->getTotalItems();
        }

        return parent::getTotalItems();
    }
}
