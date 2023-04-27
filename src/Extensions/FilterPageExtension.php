<?php

namespace WeDevelop\Elastica\Extensions;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\HasManyList;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Filters\Filter;
use WeDevelop\Elastica\Traits\Configurable;
use WeDevelop\Elastica\Traits\ElasticaConfigurable;

class FilterPageExtension extends Extension
{
    use Extensible;
    use ElasticaConfigurable;

    /** @config */
    private static array $has_many = [
        'Filters' => Filter::class,
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $this->addConfigFilters($fields);

        $filtersGridFieldConfig = GridFieldConfig_RecordEditor::create()
            ->addComponent(new GridFieldOrderableRows('Sort'))
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->addComponent(new GridFieldAddNewMultiClass());

        $fields->addFieldToTab('Root.Elastica', GridField::create(
            'Filters',
            'Filters',
            $filters = $this->owner->Filters(),
            $filtersGridFieldConfig
        ));
    }

    private function addConfigFilters(FieldList &$fields): void
    {
        $configFilters = $this->getFiltersFromConfig();

        $configFiltersGridFieldConfig = GridFieldConfig_RecordEditor::create()
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->removeComponentsByType(GridFieldDeleteAction::class)
            ->removeComponentsByType(GridFieldEditButton::class);

        $fields->addFieldToTab('Root.Elastica', GridField::create(
            'ConfigFilters',
            'ConfigFilters',
            new ArrayList($configFilters),
            $configFiltersGridFieldConfig
        )->setReadonly(true));

    }
    /**
     * @return string[]
     */
    public function getAvailableElasticaFields(): array
    {
        $fields = [];

        $filterClass = $this->getConfig('filter_class');
        if (!$filterClass::has_extension(SearchableObjectExtension::class)) {
            return $fields;
        }

        $instance = SearchableObjectExtension::createInstance($filterClass);
        $fields = array_keys($instance->getElasticaFields());

        if (method_exists($this->owner, 'updateAvailableElasticaFields')) {
            $this->owner->updateAvailableElasticaFields($fields);
        }

        $this->extend('updateAvailableElasticaFields', $fields);

        return $fields;
    }

    public function getFiltersFromConfig(): array
    {
        $filterConfig = $this->getConfig('filters');

        if (!$filterConfig) {
            return [];
        }

        $filters = [];
        foreach ($filterConfig as $filter) {
            $class = $filter['class'];
            $filterInstance = new $class();
            $filterInstance->ID = $filter['id'];
            $filterInstance->Name = $filter['name'];
            $filterInstance->Title = $filter['title'];
            $filterInstance->FieldName = $filter['field_name'];
            $filterInstance->Sort = $filter['sort'];
            $filterInstance->Editable = false;

            $filters[] = $filterInstance;
        }

        return $filters;
    }

    public function getElasticaConfig($key) {
        return $this->getConfig($key);
    }
}
