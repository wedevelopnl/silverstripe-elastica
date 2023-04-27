<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filters;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Query\AbstractQuery;
use WeDevelop\Elastica\Forms\DateFilterField;
use WeDevelop\Elastica\Interfaces\AggregatableFilterInterface;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Interfaces\FilterInterface;

/**
 * @method DateFilterField getFilterField()
 */
final class DateFilter extends Filter implements FilterInterface, AggregatableFilterInterface
{
    /** @config */
    private static string $singular_name = 'Date';

    /** @config */
    private static array $mapping = [];

    /** @config */
    private static bool $show_counts = true;

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

        $aggRange = new \Elastica\Aggregation\DateRange('range');
        $aggRange->setField($this->FieldName);
        $aggRange->setFormat('yyyy-MM-dd');

        foreach ($this->config()->get('mapping') as $key => $value) {
            $aggRange->addRange(
                date('Y-m-d', strtotime($value['From'])),
                date('Y-m-d', strtotime($value['To'])),
                $key
            );
        }

        $aggregation = new \Elastica\Aggregation\GlobalAggregation((string)$this->ID);
        $aggregation->addAggregation($aggRange);

        return $aggregation;
    }

    public function addAggregation(array $aggregation): void
    {
        $counts = [];
        foreach ($aggregation['range']['buckets'] as $value) {
            $counts[$value['key']] = $value['doc_count'];
        }

        $source = [];
        foreach ((array)$this->getFilterField()->getSource() as $key => $value) {
            if (self::config()->show_counts) {
                $source[$key] = sprintf('%s<span>%s</span>', $value, $counts[$key]);
            } else {
                $source[$key] = $value;
            }
        }

        $this->getFilterField()->setSource($source);
    }
}
