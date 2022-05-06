<?php

namespace TheWebmen\Elastica\Filters;

use Intervention\Image\Filters\FilterInterface;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Extensions\FilterIndexPageItemExtension;
use TheWebmen\Elastica\Extensions\FilterPageControllerExtension;
use TheWebmen\Elastica\Extensions\FilterPageExtension;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;

/**
 * @property string Name
 * @property string Title
 * @property string FieldName
 * @property string Sort
 * @property string PageID
 * @method SiteTree|FilterPageExtension Page
 */
class Filter extends DataObject
{
    private static $table_name = 'TheWebmen_Elastica_Filter';

    /**
     * @var FormField|null
     */
    private $field;

    private static $db = [
        'Name' => 'Varchar',
        'Title' => 'Varchar',
        'FieldName' => 'Varchar',
        'Sort' => 'Int'
    ];

    private static $required_fields = [
        'Name',
    ];

    private static $has_one = [
        'Page' => SiteTree::class
    ];

    private static $summary_fields = [
        'Name' => 'Name',
        'Title' => 'Title',
        'FieldName' => 'FieldName'
    ];

    private static $default_sort = '"Sort" ASC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Sort');
        $fields->removeByName('PageID');

        $availableFields = $this->Page()->getAvailableElasticaFields();

        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('FieldName', 'Field name', array_combine($availableFields, $availableFields))
        ]);

        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        $filters = Filter::get()->filter([
            'Name' => $this->Name,
            'PageID' => $this->PageID,
            'ID:not' => $this->ID
        ]);
        if ($filters->count() > 0) {
            $result->addError('Name already exists');
        }

        foreach (self::$required_fields as $requiredField) {
            if (empty($this->{$requiredField})) {
                $result->addError(sprintf('%s is required', $requiredField));
            }
        }

        return $result;
    }

    public function canCreate($member = null, $context = [])
    {
        return $this->baseClass() != $this->getClassName();
    }

    public function getFilterField()
    {
        return $this->field;
    }

    public function setFilterField(FilterFieldInterface $field)
    {
        $this->field = $field;
    }

    public function getElasticaQuery()
    {
        return null;
    }

    public function generateFilterField()
    {
        return null;
    }

    public function getAggregation(array $filters)
    {
        return null;
    }

    public function addAggregation(array $aggregation)
    {
        return null;
    }
}
