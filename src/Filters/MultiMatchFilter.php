<?php

namespace TheWebmen\Elastica\Filters;

use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use SilverStripe\TagField\StringTagField;
use TheWebmen\Elastica\Extensions\FilterPageControllerExtension;
use TheWebmen\Elastica\Forms\MultiMatchFilterField;
use TheWebmen\Elastica\Model\FacetIndexItemsList;

/**
 * @property string Placeholder
 * @property string AutocompleteFieldName
 * @property string AutocompleteTitleFieldName
 */
class MultiMatchFilter extends Filter
{
    private static $singular_name = 'MultiMatch';

    private static $table_name = 'TheWebmen_Elastica_Filter_MultiMatchFilter';

    private static $db = [
        'Placeholder' => 'Varchar',
        'AutocompleteFieldName' => 'Varchar',
        'AutocompleteTitleFieldName' => 'Varchar',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $availableFields = $this->Page()->getAvailableElasticaFields();
        $availableCompletionFields = $this->Page()->getAvailableElasticaCompletionFields();

        $fields->addFieldToTab('Root.Main',
            StringTagField::create(
                'FieldName',
                'FieldName',
                array_combine($availableFields, $availableFields),
                $this->FieldName ? explode(',', $this->FieldName) : null
            )
        );

        $fields->addFieldsToTab('Root.Autocomplete', [
            DropdownField::create(
                'AutocompleteFieldName',
                'Autocomplete field name',
                array_combine($availableCompletionFields, $availableCompletionFields)
            ),
            DropdownField::create(
                'AutocompleteTitleFieldName',
                'Autocomplete title field name',
                array_combine($availableFields, $availableFields),
                $this->AutocompleteTitleFieldName ? explode(',', $this->AutocompleteTitleFieldName) : null
            )
        ]);

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->Placeholder)) {
            $this->Placeholder = $this->Name;
        }
    }

    public function getElasticaQuery()
    {
        $query = null;
        $value = $this->getFilterField()->Value();

        if ($value) {
            $query = new \Elastica\Query\MultiMatch();
            $query->setQuery($value);
            $query->setFields($this->getFields());
        }

        return $query;
    }

    public function generateFilterField()
    {
        $field = new MultiMatchFilterField($this->Name, $this->Title);
        $field->setAttribute('placeholder', $this->Placeholder);

        return $field;
    }

    public function getFields()
    {
        return explode(',', $this->FieldName);
    }
}
