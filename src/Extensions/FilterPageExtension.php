<?php

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\HasManyList;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use TheWebmen\Elastica\Filters\Filter;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;

/**
 * @property FilterPageExtension owner
 * @method HasManyList|Filter[] Filters
 */
class FilterPageExtension extends DataExtension
{
    private static $has_many = [
        'Filters' => Filter::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $filtersGridFieldConfig = GridFieldConfig_RecordEditor::create()
            ->addComponent(new GridFieldOrderableRows('Sort'))
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->addComponent(new GridFieldAddNewMultiClass());

        $fields->addFieldToTab('Root.Elastica', GridField::create(
            'Filters',
            'Filters',
            $this->owner->Filters(),
            $filtersGridFieldConfig
        ));
    }

    public function getAvailableElasticaFields()
    {
        $fields = [];

        /** @var ElasticaService $elasticaService */
        $elasticaService = Injector::inst()->get('ElasticaService')->setIndex(FilterIndexPageItemExtension::getIndexName());

        foreach (FilterIndexPageItemExtension::getExtendedClasses() as $class) {
            /** @var FilterIndexItemTrait $object */
            $object = $class::singleton();

            $fields = array_merge($fields, array_keys($object->getElasticaFields()));
        }

        if (method_exists($this->owner, 'updateAvailableElasticaFields')) {
            $this->owner->updateAvailableElasticaFields($fields);
        }

        return $fields;
    }

    public function getAvailableElasticaCompletionFields()
    {
        $fields = [];

        /** @var ElasticaService $elasticaService */
        $elasticaService = Injector::inst()->get('ElasticaService')->setIndex(FilterIndexPageItemExtension::getIndexName());

        foreach (FilterIndexPageItemExtension::getExtendedClasses() as $class) {
            /** @var FilterIndexItemTrait $object */
            $object = $class::singleton();

            $fields = array_merge(
                $fields,
                $this->getAvailableElasticaCompletionFieldsFromArray($object->getElasticaFields(), '')
            );
        }

        if (method_exists($this->owner, 'updateAvailableElasticaCompletionFields')) {
            $this->owner->updateAvailableElasticaCompletionFields($fields);
        }

        return $fields;
    }

    protected function getAvailableElasticaCompletionFieldsFromArray($array, $name)
    {
        $fields = [];

        foreach ($array as $key => $field) {
            if ($field['type'] == 'completion') {
                $fields[] = ltrim("{$name}.{$key}", '.');
            }

            if (isset($field['fields'])) {
                $fields = array_merge(
                    $fields,
                    $this->getAvailableElasticaCompletionFieldsFromArray($field['fields'], "{$name}.{$key}")
                );
            }
        }

        return $fields;
    }
}
