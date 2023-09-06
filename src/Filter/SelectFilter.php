<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter;

use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use Elastica\ResultSet;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\OptionsetField;
use WeDevelop\Elastica\Factory\AggregationFactory;
use WeDevelop\Elastica\ORM\SearchList;

class SelectFilter extends Filter
{
    public const TYPE_CHECKBOX = 'Checkbox';
    public const TYPE_DROPDOWN = 'Dropdown';
    public const TYPE_RADIO = 'Radio';

    /** @config */
    private static string $singular_name = 'Select';

    /** @config */
    private static array $db = [
        'Type' => 'Enum("Checkbox,Dropdown,Radio", "Checkbox")',
    ];

    /** @config */
    private static int $max_size = 100;

    public function createFormField(): FormField
    {
        return match ($this->Type) {
            self::TYPE_DROPDOWN => DropdownField::create($this->Name, $this->Label),
            self::TYPE_RADIO => OptionsetField::create($this->Name, $this->Label),
            default => CheckboxSetField::create($this->Name, $this->Label),
        };
    }

    public function createQuery(): ?AbstractQuery
    {
        $values = $this->getFormField()->Value();
        if (!$values) {
            return null;
        }

        if (count($values) === 1) {
            return new Terms($this->FieldName, array_values($values));
        }

        $bool = new BoolQuery();

        foreach ($this->getFormField()->Value() as $value) {
            $bool->addShould(new Terms($this->FieldName, [$value]));
        }

        return $bool;
    }

    public function alterList(SearchList $list, array $filters): SearchList
    {
        $list = parent::alterList($list, $filters);

        return $list->alterQuery(function (Query $query) use ($filters) {
            $terms = new \Elastica\Aggregation\Terms($this->Name);
            $terms->setField($this->FieldName);
            $terms->setSize($this->config()->get('max_size'));
            $terms->setOrder('_key', 'asc');

            $query->addAggregation(AggregationFactory::create($this, $filters, [$terms]));
        });
    }

    public function applyContext(ResultSet $context): void
    {
        $source = [];
        foreach ($context->getAggregation($this->Name)['filter'][$this->Name]['buckets'] as $bucket) {
            $source[$bucket['key']] = sprintf('%s (%s)', $bucket['key'], $bucket['doc_count']);
        }

        $this->getFormField()->setSource($source);
    }
}
