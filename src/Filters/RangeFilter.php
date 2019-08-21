<?php

namespace TheWebmen\Elastica\Filters;

use Elastica\Query\AbstractQuery;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form;
use TheWebmen\Elastica\Forms\RangeFilterField;

/**
 * @method RangeFilterField getFilterField
 */
class RangeFilter extends Filter
{
    private static $singular_name = 'Range';

    public function getElasticaQuery()
    {
        $query = null;
        $value = $this->getFilterField()->Value();

        if (is_array($value) && isset($value['From']) && isset($value['To']) && $value['From'] != '' && $value['To'] != '') {
            $query = new \Elastica\Query\Range($this->FieldName, [
                'gte' => (int)$value['From'],
                'lte' => (int)$value['To'],
            ]);
        }

        return $query;
    }

    public function generateFilterField()
    {
        return new RangeFilterField($this->Name, $this->Title);
    }

    /**
     * @param Filter[] $filters
     * @return \Elastica\Aggregation\GlobalAggregation|null
     */
    public function getAggregation(array $filters)
    {
        $query = new \Elastica\Query\BoolQuery();

        foreach ($filters as $filter) {
            $filterQuery = $filter->getElasticaQuery();

            if ($this->ID != $filter->ID && $filterQuery) {
                $query->addMust($filterQuery);
            }
        }

        $min = new \Elastica\Aggregation\Min('min');
        $min->setField($this->FieldName);

        $max = new \Elastica\Aggregation\Max('max');
        $max->setField($this->FieldName);

        $filter = new \Elastica\Aggregation\Filter('filter', $query);
        $filter->addAggregation($min);
        $filter->addAggregation($max);

        $aggregation = new \Elastica\Aggregation\GlobalAggregation($this->ID);
        $aggregation->addAggregation($filter);

        return $aggregation;
    }

    public function addAggregation(array $aggregation)
    {
        $this->getFilterField()->setValue([
            'From' => $aggregation['filter']['min']['value'],
            'To' => $aggregation['filter']['max']['value']
        ]);
    }
}
