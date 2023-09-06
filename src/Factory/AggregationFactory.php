<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Factory;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\GlobalAggregation;
use Elastica\Query\BoolQuery;
use WeDevelop\Elastica\Filter\Filter;

class AggregationFactory
{
    public static function create(Filter $filter, array $filters, array $aggs): AbstractAggregation
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

        $aggregation = new \Elastica\Aggregation\Filter('filter', $bool);

        foreach ($aggs as $agg) {
            $aggregation->addAggregation($agg);
        }

        return (new GlobalAggregation($filter->Name))
            ->addAggregation($aggregation);
    }
}
