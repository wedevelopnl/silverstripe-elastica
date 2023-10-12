<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter;

use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\ResultSet;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\OptionsetField;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Factory\AggregationFactory;
use WeDevelop\Elastica\Filter\RangeFilter\Option;
use WeDevelop\Elastica\Form\RangeInputField;
use WeDevelop\Elastica\Form\RangeSliderField;
use WeDevelop\Elastica\ORM\SearchList;

class RangeFilter extends Filter
{
    public const TYPE_SLIDER = 'Slider';
    public const TYPE_INPUT = 'Input';
    public const TYPE_CHECKBOX = 'Checkbox';
    public const TYPE_DROPDOWN = 'Dropdown';
    public const TYPE_RADIO = 'Radio';

    /** @config */
    private static string $singular_name = 'Range';

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
            $options = $fields->dataFieldByName('Options');
            if ($options instanceof GridField) {
                $options->getConfig()
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
            }
        });

        return parent::getCMSFields();
    }

    public function createFormField(): FormField
    {
        return match ($this->Type) {
            self::TYPE_INPUT => RangeInputField::create($this->Name, $this->Label),
            self::TYPE_CHECKBOX => CheckboxSetField::create($this->Name, $this->Label),
            self::TYPE_DROPDOWN => DropdownField::create($this->Name, $this->Label),
            self::TYPE_RADIO => OptionsetField::create($this->Name, $this->Label),
            default => RangeSliderField::create($this->Name, $this->Label),
        };
    }

    public function createQuery(): ?AbstractQuery
    {
        $values = $this->getFormField()->Value();
        if (empty($values['From']) && empty($values['To'])) {
            return null;
        }

        $args = [];
        if (!empty($values['From'])) {
            $args['gte'] = $values['From'];
        }
        if (!empty($values['To'])) {
            $args['lte'] = $values['To'];
        }

        return new Query\Range($this->FieldName, $args);
    }

    public function applyContext(ResultSet $context): void
    {
        if ($this->hasOptions()) {
            $source = [];
            foreach ($context->getAggregation($this->Name)['filter'][$this->Name]['buckets'] as $bucket) {
                $source[$bucket['key']] = sprintf('%s (%s)', $bucket['key'], $bucket['doc_count']);
            }

            $this->getFormField()->setSource($source);
        } else {
            $this->getFormField()->setMin($context->getAggregation($this->Name)['filter']['min']['value']);
            $this->getFormField()->setMax($context->getAggregation($this->Name)['filter']['max']['value']);
        }
    }

    public function alterList(SearchList $list, array $filters): SearchList
    {
        $list = parent::alterList($list, $filters);

        if ($this->hasOptions()) {
            return $list->alterQuery(function (Query $query) use ($filters) {
                $range = new \Elastica\Aggregation\Range($this->Name);
                $range->setField($this->FieldName);

                $query->addAggregation(AggregationFactory::singleton()->create($this, $filters, [$range]));
            });
        }

        return $list->alterQuery(function (Query $query) use ($filters) {
            $min = new \Elastica\Aggregation\Min('min');
            $min->setField($this->FieldName);
            $max = new \Elastica\Aggregation\Max('max');
            $max->setField($this->FieldName);

            $query->addAggregation(AggregationFactory::singleton()->create($this, $filters, [$min, $max]));
        });
    }

    private function hasOptions(): bool
    {
        return in_array($this->Type, [self::TYPE_CHECKBOX, self::TYPE_DROPDOWN, self::TYPE_RADIO], true);
    }
}
