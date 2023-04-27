<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filters;

use Elastica\Query\AbstractQuery;
use WeDevelop\Elastica\Forms\GeoDistanceFilterField;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Interfaces\FilterInterface;
use WeDevelop\Elastica\Services\GeocodeService;

/**
 * @property string $Placeholder
 */
final class GeoDistanceFilter extends Filter implements FilterInterface
{
    /** @config */
    private static string $singular_name = 'GeoDinstance';

    /** @config */
    private static string $table_name = 'WeDevelop_Elastica_Filter_GeoDistanceFilter';

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
