<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Filters;

use Elastica\Query\AbstractQuery;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\TagField\StringTagField;
use TheWebmen\Elastica\Forms\MultiMatchFilterField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Interfaces\FilterInterface;

/**
 * @property string $Placeholder
 * @property string $AutocompleteFieldName
 * @property string $AutocompleteTitleFieldName
 */
final class MultiMatchFilter extends Filter implements FilterInterface
{
    /** @config */
    private static string $singular_name = 'MultiMatch';

    /** @config */
    private static string $table_name = 'TheWebmen_Elastica_Filter_MultiMatchFilter';

    /** @config */
    private static array $db = [
        'Placeholder' => 'Varchar',
        'AutocompleteFieldName' => 'Varchar',
        'AutocompleteTitleFieldName' => 'Varchar',
    ];

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $availableFields = $this->Page()->getAvailableElasticaFields();
        $availableCompletionFields = $this->Page()->getAvailableElasticaCompletionFields();

        $fields->addFieldToTab(
            'Root.Main',
            StringTagField::create(
                'FieldName',
                'FieldName',
                array_combine($availableFields, $availableFields),
                $this->getFields(),
            )
        );

        $fields->addFieldsToTab('Root.Autocomplete', [
            DropdownField::create(
                'AutocompleteFieldName',
                'Autocomplete field name',
                array_combine($availableCompletionFields, $availableCompletionFields),
            ),
            DropdownField::create(
                'AutocompleteTitleFieldName',
                'Autocomplete title field name',
                array_combine($availableFields, $availableFields),
                $this->AutocompleteTitleFieldName,
            ),
        ]);

        return $fields;
    }

    public function getElasticaQuery(): ?AbstractQuery
    {
        $value = $this->getFilterField()->Value();

        if (empty($value)) {
            return null;
        }

        $query = new \Elastica\Query\MultiMatch();
        $query->setQuery($value);
        $query->setFields($this->getFields());

        return $query;
    }

    public function generateFilterField(): FilterFieldInterface
    {
        $field = new MultiMatchFilterField($this->Name, $this->Title);
        $field->setAttribute('placeholder', $this->Placeholder);

        return $field;
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return $this->FieldName ? explode(',', $this->FieldName) : [];
    }
}
