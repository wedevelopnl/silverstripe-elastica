<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\CMS\Model\SiteTree;
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

/**
 * @property FilterPageExtension $owner
 * @method HasManyList|Filter[] Filters()
 * @mixin SiteTree
 */
final class FilterPageExtension extends DataExtension
{
    /** @config */
    private static array $has_many = [
        'Filters' => Filter::class,
    ];

    public function updateCMSFields(FieldList $fields): void
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

    /**
     * @return string[]
     */
    public function getAvailableElasticaFields(): array
    {
        $fields = [];

        ElasticaService::singleton()->setIndex(FilterIndexPageItemExtension::getIndexName());

        foreach (FilterIndexPageItemExtension::getExtendedClasses() as $class) {
            /** @var FilterIndexPageItemExtension $object */
            $object = $class::singleton();

            $fields = array_merge($fields, array_keys($object->getElasticaFields()));
        }

        if (method_exists($this->owner, 'updateAvailableElasticaFields')) {
            $this->owner->updateAvailableElasticaFields($fields);
        }

        return $fields;
    }
}
