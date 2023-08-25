<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Extension;

use App\ORM\SortOption;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Filter\Filter;

class FilterPageExtension extends Extension
{
    /** @config */
    private static array $has_many = [
        'Filters' => Filter::class,
        'SortOptions' => SortOption::class,
    ];

    public function updateCMSFields(FieldList $fields): FieldList
    {
        $fields->addFieldsToTab('Root.Elastica.Filters', [
            GridField::create('Filters', 'Filters', $this->getOwner()->Filters(), GridFieldConfig_RecordEditor::create()
                ->addComponent(GridFieldAddNewMultiClass::create())
                ->addComponent(GridFieldOrderableRows::create())),
        ]);

        $fields->addFieldsToTab('Root.Elastica.Sort', [
            GridField::create('SortOptions', 'Sorts', $this->getOwner()->SortOptions(), GridFieldConfig_RecordEditor::create()
                ->addComponent(GridFieldOrderableRows::create())),
        ]);

        return $fields;
    }
}
