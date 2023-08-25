<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Factory;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Query\BoolQuery;
use WeDevelop\Elastica\Filter\Filter;

class AggregationFactory
{
    public static function create(Filter $filter, array $filters, AbstractAggregation $aggregation): AbstractAggregation
    {
        $bool = new BoolQuery();

        array_walk($filters, function (Filter $value) use ($bool, $filter) {
            if ($value === $filter) {
                return;
            }

            $query = $value->createQuery();
            if (!$query) {
                return;
            }

            $bool->addMust($query);
        });

        return (new \Elastica\Aggregation\Filter($filter->Name, $bool))
            ->addAggregation($aggregation);
    }
}
