<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Filters;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Query\AbstractQuery;
use TheWebmen\Elastica\Forms\RangeFilterField;
use TheWebmen\Elastica\Interfaces\AggregatableFilterInterface;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Interfaces\FilterInterface;

/**
 * @method RangeFilterField getFilterField()
 */
final class RangeFilter extends Filter implements FilterInterface, AggregatableFilterInterface
{
    /** @config */
    private static string $singular_name = 'Range';

    public function getElasticaQuery(): ?AbstractQuery
    {
        $value = $this->getFilterField()->Value();

        if (empty($value['From']) || empty($value['To'])) {
            return null;
        }

        return new \Elastica\Query\Range($this->FieldName, [
            'gte' => (int)$value['From'],
            'lte' => (int)$value['To'],
        ]);
    }

    public function generateFilterField(): FilterFieldInterface
    {
        return new RangeFilterField($this->Name, $this->Title);
    }

    /**
     * @param Filter[] $filters
     */
    public function getAggregation(array $filters): AbstractAggregation
    {
        $query = new \Elastica\Query\BoolQuery();

        $min = new \Elastica\Aggregation\Min('min');
        $min->setField($this->FieldName);

        $max = new \Elastica\Aggregation\Max('max');
        $max->setField($this->FieldName);

        $filter = new \Elastica\Aggregation\Filter('filter', $query);
        $filter->addAggregation($min);
        $filter->addAggregation($max);

        $aggregation = new \Elastica\Aggregation\GlobalAggregation((string)$this->ID);
        $aggregation->addAggregation($filter);

        return $aggregation;
    }

    public function addAggregation(array $aggregation): void
    {
        $field = $this->getFilterField();
        $field->setMin($aggregation['filter']['min']['value']);
        $field->setMax($aggregation['filter']['max']['value']);
        $field->setValue([
            'From' => $aggregation['filter']['min']['value'],
            'To' => $aggregation['filter']['max']['value'],
        ]);
    }
}
