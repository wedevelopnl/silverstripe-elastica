<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Interfaces;

use SilverStripe\Forms\FormField;
use WeDevelop\Elastica\Filters\Filter;

/**
 * @mixin FormField
 */
interface FilterFieldInterface
{
    public function getFilter(): Filter;

    public function setFilter(Filter $filter): void;
}
