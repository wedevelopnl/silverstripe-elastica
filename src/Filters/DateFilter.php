<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Filters;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Query\AbstractQuery;
use TheWebmen\Elastica\Forms\DateFilterField;
use TheWebmen\Elastica\Interfaces\AggregatableFilterInterface;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Interfaces\FilterInterface;

/**
 * @method DateFilterField getFilterField()
 */
final class DateFilter extends Filter implements FilterInterface, AggregatableFilterInterface
{
    /** @config */
    private static string $singular_name = 'Date';

    /** @config */
    private static array $mapping = [];

    public function getElasticaQuery(): ?AbstractQuery
    {
        $value = $this->getFilterField()->Value();
        $mapping = $this->config()->get('mapping');

        if (!array_key_exists($value, $mapping)) {
            return null;
        }

        return new \Elastica\Query\Range($this->FieldName, [
            'gt' => date('Y-m-d', strtotime($mapping[$value]['From'])),
            'lt' => date('Y-m-d', strtotime($mapping[$value]['To'])),
        ]);
    }

    public function generateFilterField(): FilterFieldInterface
    {
        $options = [];

        foreach ($this->config()->get('mapping') as $key => $settings) {
            if (!array_key_exists('Exclude', $settings) || !$settings['Exclude']) {
                $options[$key] = $settings['Label'];
            }
        }

        return new DateFilterField($this->Name, $this->Title, $options);
    }

    /**
     * @param Filter[] $filters
     */
    public function getAggregation(array $filters): AbstractAggregation
    {
        $query = new \Elastica\Query\BoolQuery();

        foreach ($filters as $filter) {
            $filterQuery = $filter->getElasticaQuery();

            if ($this->ID !== $filter->ID && $filterQuery) {
                $query->addMust($filterQuery);
            }
        }

        $this->extend('updateAggregationQuery', $query);

        $aggRange = new \Elastica\Aggregation\Range('range');
        $aggRange->setField($this->FieldName);

        foreach ($this->config()->get('mapping') as $key => $value) {
            $aggRange->addRange(
                date('Y-m-d', strtotime($value['From'])),
                date('Y-m-d', strtotime($value['To'])),
                $key
            );
        }

        $filter = new \Elastica\Aggregation\Filter('filter', $query);
        $filter->addAggregation($aggRange);

        $aggregation = new \Elastica\Aggregation\GlobalAggregation((string)$this->ID);
        $aggregation->addAggregation($filter);

        return $aggregation;
    }

    public function addAggregation(array $aggregation): void
    {
        $counts = [];
        foreach ($aggregation['filter']['range']['buckets'] as $value) {
            $counts[$value['key']] = $value['doc_count'];
        }

        $source = [];
        foreach ((array)$this->getFilterField()->getSource() as $key => $value) {
            $source[$key] = sprintf('%s<span>%s</span>', $value, $counts[$key]);
        }

        $this->getFilterField()->setSource($source);
    }
}
