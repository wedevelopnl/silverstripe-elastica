<?php

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

class GeoDistanceFilterField extends FormField implements FilterFieldInterface
{
    use FilterFieldTrait;

    private static $distance_options = [
        '10km' => '10 Km',
        '20km' => '20 Km',
        '50km' => '50 Km',
        '100km' => '100 Km',
        '150km' => '150 Km',
        '200km' => '200 Km',
    ];

    protected $searchField = null;

    protected $distanceField = null;

    public function __construct($name, $title)
    {
        $this->searchField = TextField::create(
            $name . '[Search]',
            _t(self::class . '.SEARCH_TITLE', 'Location')
        );

        $this->distanceField = DropdownField::create(
            $name . '[Distance]',
            _t(self::class . '.DISTANCE_TITLE', 'Distance'),
            self::config()->get('distance_options')
        );

        parent::__construct($name, $title);
    }

    public function setValue($value, $data = null)
    {
        parent::setValue($value, $data);

        if (is_array($value)) {
            $this->searchField->setValue($value['Search']);
            $this->distanceField->setValue($value['Distance']);
        }

        return $this;
    }

    public function getSearchField()
    {
        return $this->searchField;
    }

    public function getDistanceField()
    {
        return $this->distanceField;
    }
}
