<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Filters;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\GlobalAggregation;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Query\Terms;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use TheWebmen\Elastica\Forms\TermsFilterCheckboxSetField;
use TheWebmen\Elastica\Forms\TermsFilterDropdownField;
use TheWebmen\Elastica\Forms\TermsFilterOptionsetField;
use TheWebmen\Elastica\Interfaces\AggregatableFilterInterface;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Interfaces\FilterInterface;

/**
 * @property string $Type
 * @property string $Placeholder
 * @method TermsFilterCheckboxSetField|TermsFilterDropdownField|TermsFilterOptionsetField getFilterField()
 */
final class TermsFilter extends Filter implements FilterInterface, AggregatableFilterInterface
{
    private const TYPE_CHECKBOX = 'checkbox';
    private const TYPE_DROPDOWN = 'dropdown';
    private const TYPE_RADIO = 'radio';

    /** @config */
    private static string $singular_name = 'Terms';

    /** @config */
    private static string $table_name = 'TheWebmen_Elastica_Filter_TermsFilter';

    /** @config */
    private static array $db = [
        'Type' => 'Varchar',
        'Placeholder' => 'Varchar',
    ];

    /** @config */
    private static bool $show_counts = true;

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', DropdownField::create('Type', 'Type', [
            self::TYPE_CHECKBOX => self::TYPE_CHECKBOX,
            self::TYPE_DROPDOWN => self::TYPE_DROPDOWN,
            self::TYPE_RADIO => self::TYPE_RADIO,
        ]), 'Name');

        return $fields;
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if (empty($this->Placeholder)) {
            $this->Placeholder = $this->Name;
        }
    }

    public function getElasticaQuery(): ?AbstractQuery
    {
        $value = $this->getFilterField()->Value();

        if (in_array($this->Type, [self::TYPE_CHECKBOX, self::TYPE_RADIO], true)) {
            $value = is_array($value) ? $value : [];
        }

        $this->extend('updateValue', $value);

        $query = null;

        if ($value && is_array($value)) {
            $query = new Terms($this->FieldName, array_keys($value));
        } elseif (!empty($value)) {
            $query = new Term();
            $query->setTerm($this->FieldName, $value);
        }

        return $query;
    }

    public function generateFilterField(): FilterFieldInterface
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

    /**
     * @param Filter[] $filters
     */
    public function getAggregation(array $filters): AbstractAggregation
    {
        $aggFilterQuery = new BoolQuery();

        foreach ($filters as $aggFilterFilter) {
            $aggFilterFilterQuery = $aggFilterFilter->getElasticaQuery();

            if ($this->ID !== $aggFilterFilter->ID && $aggFilterFilterQuery) {
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

        $globalAgg = new GlobalAggregation((string)$this->ID);
        $globalAgg->addAggregation($aggFilter);

        return $globalAgg;
    }

    public function addAggregation(array $aggregation): void
    {
        $source = [];

        foreach ($aggregation['filter']['terms']['buckets'] as $bucket) {
            if ($bucket['doc_count'] > 0) {
                $source[$bucket['key']] = $this->generateLabel($bucket['key'], $bucket['doc_count']);
            }
        }

        $this->getFilterField()->setSource($source);
    }

    private function generateLabel(string $label, int $count): string
    {
        if (!self::config()->show_counts) {
            return $label;
        }

        return sprintf(
            '%s%s%s%s',
            $label,
            $this->Type === self::TYPE_DROPDOWN ? ' (' : '<span>',
            $count,
            $this->Type === self::TYPE_DROPDOWN ? ')' : '</span>',
        );
    }
}
