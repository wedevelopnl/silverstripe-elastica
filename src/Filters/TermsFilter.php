<?php

namespace TheWebmen\Elastica\Filters;

use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\OptionsetField;
use TheWebmen\Elastica\Forms\TermsFilterCheckboxSetField;
use TheWebmen\Elastica\Forms\TermsFilterDropdownField;
use TheWebmen\Elastica\Forms\TermsFilterField;
use TheWebmen\Elastica\Forms\TermsFilterOptionsetField;

/**
 * @property string Type
 * @property string Placeholder
 * @method TermsFilterField getFilterField
 */
class TermsFilter extends Filter
{
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_DROPDOWN = 'dropdown';
    const TYPE_RADIO = 'radio';

    private static $singular_name = 'Terms';

    private static $table_name = 'TheWebmen_Elastica_Filter_TermsFilter';

    private static $db = [
        'Type' => 'Varchar',
        'Placeholder' => 'Varchar'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', DropdownField::create('Type', 'Type', [
            self::TYPE_CHECKBOX => self::TYPE_CHECKBOX,
            self::TYPE_DROPDOWN => self::TYPE_DROPDOWN,
            self::TYPE_RADIO => self::TYPE_RADIO,
        ]), 'Name');

        return $fields;
    }

    public function getElasticaQuery()
    {
        $value = $this->getFilterField()->Value();

        if (in_array($this->Type, [self::TYPE_CHECKBOX, self::TYPE_RADIO])) {
            $value = is_array($value) ? $value : [];
        }

        $this->extend('updateValue', $value);

        $query = null;

        if ($value && is_array($value)) {
            $query = new \Elastica\Query\Terms($this->FieldName, $value);
        } elseif (!empty($value)) {
            $query = new \Elastica\Query\Term();
            $query->setTerm($this->FieldName, $value);
        }

        return $query;
    }

    public function generateFilterField()
    {
        switch ($this->Type) {
            case self::TYPE_DROPDOWN:
                $field = TermsFilterDropdownField::create($this->Name, $this->Title)
                    ->setEmptyString($this->Placeholder);
                break;
            case self::TYPE_RADIO:
                $field = TermsFilterOptionsetField::create($this->Name, $this->Title);
                break;
            default:
                $field = TermsFilterCheckboxSetField::create($this->Name, $this->Title);
                break;
        }

        return $field;
    }

    public function generateLabel($label, $count)
    {
        if ($this->Type == self::TYPE_DROPDOWN) {
            $label = "{$label} ({$count})";
        } else {
            $label = "{$label}<span>({$count})</span>";
        }

        return $label;
    }

    /**
     * @param Filter[] $filters
     * @return \Elastica\Aggregation\GlobalAggregation|null
     */
    public function getAggregation(array $filters)
    {
        $aggFilterQuery = new \Elastica\Query\BoolQuery();

        foreach ($filters as $aggFilterFilter) {
            $aggFilterFilterQuery = $aggFilterFilter->getElasticaQuery();

            if ($this->ID != $aggFilterFilter->ID && $aggFilterFilterQuery) {
                $aggFilterQuery->addMust($aggFilterFilterQuery);
            }
        }

        $this->extend('updateAggregationQuery', $aggFilterQuery);

        $agg = new \Elastica\Aggregation\Terms('terms');
        $agg->setField($this->FieldName);
        $agg->setOrder('_term', 'asc');
        $agg->setSize(999);

        $aggFilter = new \Elastica\Aggregation\Filter('filter', $aggFilterQuery);
        $aggFilter->addAggregation($agg);

        $globalAgg = new \Elastica\Aggregation\GlobalAggregation($this->ID);
        $globalAgg->addAggregation($aggFilter);

        return $globalAgg;
    }

    public function addAggregation(array $aggregation)
    {
        $source = [];

        foreach ($aggregation['filter']['terms']['buckets'] as $bucket) {
            if ($bucket['doc_count'] > 0) {
                $source[$bucket['key']] = $this->generateLabel($bucket['key'], $bucket['doc_count']);
            }
        }

        $this->getFilterField()->setSource($source);
    }
}
