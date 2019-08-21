<?php

namespace TheWebmen\Elastica\Filters;

use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form;
use TheWebmen\Elastica\Forms\GeoDistanceFilterField;

/**
 * @property string Placeholder
 */
class GeoDistanceFilter extends Filter
{
    private static $singular_name = 'GeoDinstance';

    private static $table_name = 'TheWebmen_Elastica_Filter_GeoDistanceFilter';

    private static $db = [
        'Placeholder' => 'Varchar'
    ];

    public function getElasticaQuery()
    {
        $query = null;
        $value = $this->getFilterField()->Value();
        $search = urlencode($value['Search']);
        
        $this->extend('updateValue', $value);

        $mapsKey = self::config()->get('maps_key');
        if (!$mapsKey) {
            throw new \Exception('Maps key is empty');
        }

        if ($value['Search']) {
            $data = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($search) . "&key={$mapsKey}");
            $data = json_decode($data, true);
            if ($data['status'] == 'OK') {
                $location = $data['results'][0]['geometry']['location'];
                $distance = !empty($value['Distance']) ? $value['Distance'] : '10km';

                $query = new \Elastica\Query\GeoDistance($this->FieldName, "{$location['lat']},{$location['lng']}", $distance);
            }
        }

        return $query;
    }

    public function generateFilterField()
    {
        $field = new GeoDistanceFilterField($this->Name, $this->Title);
        $field->getSearchField()->setAttribute('placeholder', $this->Placeholder);

        return $field;
    }
}
