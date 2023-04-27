<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filters;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use WeDevelop\Elastica\Extensions\FilterPageExtension;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Interfaces\FilterInterface;

/**
 * @property string $Name
 * @property string $Title
 * @property string $FieldName
 * @property int $Sort
 * @method FilterPageExtension Page()
 * @mixin FilterInterface
 */
class Filter extends DataObject
{
    /** @config */
    private static string $table_name = 'WeDevelop_Elastica_Filter';

    /** @config */
    private static array $db = [
        'Name' => 'Varchar',
        'Title' => 'Varchar',
        'FieldName' => 'Varchar',
        'Sort' => 'Int',
    ];

    /** @config */
    private static array $has_one = [
        'Page' => SiteTree::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'Name',
        'Title',
        'FieldName',
    ];

    /** @config */
    private static string $default_sort = '"Sort" ASC';

    private FilterFieldInterface $field;

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Sort');
        $fields->removeByName('PageID');

        $availableFields = $this->Page()->getAvailableElasticaFields();

        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('FieldName', 'Field name', array_combine($availableFields, $availableFields)),
        ]);

        return $fields;
    }

    /**
     * @param array<int, mixed> $context
     */
    public function canCreate($member = null, $context = []): bool
    {
        return $this->baseClass() !== $this->getClassName();
    }

    public function getFilterField(): FilterFieldInterface
    {
        return $this->field;
    }

    public function setFilterField(FilterFieldInterface $field): void
    {
        $this->field = $field;
    }
}
