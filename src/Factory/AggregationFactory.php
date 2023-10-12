<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Factory;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\GlobalAggregation;
use Elastica\Query\BoolQuery;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use WeDevelop\Elastica\Filter\Filter;

class AggregationFactory
{
    use Injectable;
    use Extensible;

    public function create(Filter $filter, array $filters, array $aggs): AbstractAggregation
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

        $this->extend('updateAggregation', $aggregation);

        return (new GlobalAggregation($filter->Name))
            ->addAggregation($aggregation);
    }
}
