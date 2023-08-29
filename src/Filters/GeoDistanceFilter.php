<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Filters;

use Elastica\Query\AbstractQuery;
use TheWebmen\Elastica\Forms\GeoDistanceFilterField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Interfaces\FilterInterface;
use TheWebmen\Elastica\Services\GeocodeService;

/**
 * @property string $Placeholder
 */
final class GeoDistanceFilter extends Filter implements FilterInterface
{
    /** @config */
    private static string $singular_name = 'GeoDistance';

    /** @config */
    private static string $table_name = 'TheWebmen_Elastica_Filter_GeoDistanceFilter';

    /** @config */
    private static array $db = [
        'Placeholder' => 'Varchar',
    ];

    public function getElasticaQuery(): ?AbstractQuery
    {
        $value = $this->getFilterField()->Value();

        if (empty($value['Search'])) {
            return null;
        }

        $this->extend('updateValue', $value);

        $location = GeocodeService::singleton()->geocode($value['Search']);

        if (!$location) {
            return null;
        }

        if (empty($value['Distance'])) {
            return null;
        }

        return new \Elastica\Query\GeoDistance(
            $this->FieldName,
            $location,
            $value['Distance'],
        );
    }

    public function generateFilterField(): FilterFieldInterface
    {
        $field = new GeoDistanceFilterField($this->Name, $this->Title);
        $field->getSearchField()->setAttribute('placeholder', $this->Placeholder);

        return $field;
    }
}
