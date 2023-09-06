<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Form;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;

class DistanceField extends FormField
{
    private TextField $addressField;

    private DropdownField $distanceField;

    public function __construct(string $name, string $addressTitle, string $distanceTitle, array $distances)
    {
        $this->addressField = TextField::create(
            $name . '[Address]',
            $addressTitle,
        );

        $this->distanceField = DropdownField::create(
            $name . '[Distance]',
            $distanceTitle,
            $distances,
        );

        parent::__construct($name);
    }

    public function setValue($value, $data = null): self
    {
        parent::setValue($value, $data);

        $this->addressField->setValue($value['Address'] ?? null);
        $this->distanceField->setValue($value['Distance'] ?? null);

        return $this;
    }

    public function getAddressField(): TextField
    {
        return $this->addressField;
    }

    public function getDistanceField(): DropdownField
    {
        return $this->distanceField;
    }
}
