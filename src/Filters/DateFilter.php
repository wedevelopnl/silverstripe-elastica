<?php

namespace TheWebmen\Elastica\Filters;

use Elastica\Query\AbstractQuery;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use TheWebmen\Elastica\Forms\DateFilterField;

/**
 * @method DateFilterField getFilterField
 */
class DateFilter extends Filter
{
    private static $singular_name = 'Date';

    private static $mapping = [
        'Today' => [
            'From' => 'today midnight',
            'To' => 'tomorrow midnight'
        ],
        'Since 7 days' => [
            'From' => '-7 days midnight',
            'To' => 'tomorrow midnight'
        ],
        'Since 30 days' => [
            'From' => '-30 days midnight',
            'To' => 'tomorrow midnight'
        ]
    ];

    public function getElasticaQuery()
    {
        $query = null;
        $value = $this->getFilterField()->Value();
        $mapping = $this->config()->get('mapping');

        if ($value && array_key_exists($value, $mapping)) {
            $query = new \Elastica\Query\Range($this->FieldName, [
                'gt' => DBField::create_field('Datetime', strtotime($mapping[$value]['From']))->Value,
                'lt' => DBField::create_field('Datetime', strtotime($mapping[$value]['To']))->Value,
            ]);
        }

        return $query;
    }

    public function generateFilterField()
    {
        $values = array_keys($this->config()->get('mapping'));
        return new DateFilterField($this->Name, $this->Title, array_combine($values, $values));
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

        $aggRange = new \Elastica\Aggregation\Range('range');
        $aggRange->setField($this->FieldName);

        foreach ($this->config()->get('mapping') as $key => $value) {
            $aggRange->addRange(
                DBField::create_field('Datetime', strtotime($value['From']))->Value,
                DBField::create_field('Datetime', strtotime($value['To']))->Value,
                $key
            );
        }

        $filter = new \Elastica\Aggregation\Filter('filter', $query);
        $filter->addAggregation($aggRange);

        $aggregation = new \Elastica\Aggregation\GlobalAggregation($this->ID);
        $aggregation->addAggregation($filter);

        return $aggregation;
    }

    public function addAggregation(array $aggregation)
    {
        $counts = [];
        foreach ($aggregation['filter']['range']['buckets'] as $value) {
            $counts[$value['key']] = $value['doc_count'];
        }

        $source = [];
        foreach ($this->getFilterField()->getSource() as $key => $value) {
            $source[$key] = "{$value}<span>({$counts[$key]})</span>";
        }

        $this->getFilterField()->setSource($source);
    }
}
