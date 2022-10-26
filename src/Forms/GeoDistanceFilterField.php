<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class GeoDistanceFilterField extends FormField implements FilterFieldInterface
{
    use FilterFieldTrait;

    private TextField $searchField;

    private DropdownField $distanceField;

    public function __construct($name, $title)
    {
        $this->searchField = TextField::create(
            $name . '[Search]',
            _t(self::class . '.SEARCH_TITLE', 'Location'),
        );

        $this->distanceField = DropdownField::create(
            $name . '[Distance]',
            _t(self::class . '.DISTANCE_TITLE', 'Distance'),
            self::config()->get('distance_options') ?? [],
        );

        parent::__construct($name, $title);
    }

    /**
     * @param array<string, mixed>|DataObject $data
     */
    public function setValue($value, $data = null): self
    {
        parent::setValue($value, $data);

        $this->searchField->setValue($value['Search'] ?? null);
        $this->distanceField->setValue($value['Distance'] ?? null);

        return $this;
    }

    public function getSearchField(): TextField
    {
        return $this->searchField;
    }

    public function getDistanceField(): DropdownField
    {
        return $this->distanceField;
    }
}
