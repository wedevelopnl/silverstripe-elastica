<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Interfaces;

use Elastica\Aggregation\AbstractAggregation;
use TheWebmen\Elastica\Filters\Filter;

interface AggregatableFilterInterface
{
    /**
     * @param Filter[] $filters
     */
    public function getAggregation(array $filters): AbstractAggregation;

    /**
     * @param array<string, mixed> $aggregation
     */
    public function addAggregation(array $aggregation): void;
}
