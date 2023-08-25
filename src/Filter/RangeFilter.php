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
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\OptionsetField;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Filter\DistanceFilter\Option;

class RangeFilter extends Filter
{
    public const TYPE_SLIDER = 'Slider';
    public const TYPE_INPUT = 'Input';
    public const TYPE_CHECKBOX = 'Checkbox';
    public const TYPE_DROPDOWN = 'Dropdown';
    public const TYPE_RADIO = 'Radio';

    /** @config */
    private static string $singular_name = 'Select';

    /** @config */
    private static array $db = [
        'Type' => 'Enum("Slider,Input,Checkbox,Dropdown,Radio", "Slider")',
    ];

    /** @config */
    private static array $has_many = [
        'Options' => Option::class,
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            /** @var GridFieldConfig $config */
            $config = $fields->dataFieldByName('Options')->getConfig();

            $config
                ->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldDataColumns::class,
                    GridField_ActionMenu::class,
                    GridFieldEditButton::class,
                    GridFieldDeleteAction::class,
                ])
                ->addComponents(
                    GridFieldEditableColumns::create(),
                    GridFieldAddNewInlineButton::create(),
                    GridFieldOrderableRows::create(),
                    GridFieldDeleteAction::create()
                );
        });

        return parent::getCMSFields();
    }


    public function createFormField(): FormField
    {
        return match ($this->Type) {
            self::TYPE_INPUT => RangeInputField::create($this->Name, $this->Name),
            self::TYPE_CHECKBOX => CheckboxSetField::create($this->Name, $this->Name),
            self::TYPE_DROPDOWN => DropdownField::create($this->Name, $this->Name),
            self::TYPE_RADIO => OptionsetField::create($this->Name, $this->Name),
            default => RangeSliderField::create($this->Name, $this->Name),
        };
    }

    public function createQuery(): ?AbstractQuery
    {
        $values = $this->getFormField()->Value();
        if (!$values) {
            return null;
        }

        // TODO: implement
        return new Query\Range($this->Name, $values);
    }

    public function applyContext(ResultSet $context): void
    {
        if (!$this->hasOptions()) {
            return;
        }

        $source = [];
        foreach ($context->getAggregation($this->Name)[$this->Name]['buckets'] as $bucket) {
            $source[$bucket['key']] = sprintf('%s (%s)', $bucket['key'], $bucket['doc_count']);
        }

        $this->getFormField()->setSource($source);
    }

    public function alterQuery(Query $query, array $filters): void
    {
        if (!$this->hasOptions()) {
            return;
        }

        $terms = new \Elastica\Aggregation\Terms($this->Name);
        $terms->setField($this->Name);
        $terms->setOrder('_term', 'asc');

        $aggregation = new \Elastica\Aggregation\Filter($this->Name, $this->createFiltersQuery($filters));
        $aggregation->addAggregation($terms);

        $query->addAggregation($aggregation);
    }

    private function hasOptions(): bool
    {
        return in_array($this->Type, [self::TYPE_CHECKBOX, self::TYPE_DROPDOWN, self::TYPE_RADIO], true);
    }
}
