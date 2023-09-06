<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Form;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBHTMLText;

class RangeInputField extends FormField
{
    private TextField $fromField;

    private TextField $toField;

    private ?float $min = null;

    private ?float $max = null;

    public function __construct(string $name, ?string $title = null)
    {
        $this->fromField = TextField::create($name . '[From]', '');
        $this->toField = TextField::create($name . '[To]', '');

        parent::__construct($name, $title);
    }

    public function setValue($value, $data = null): self
    {
        parent::setValue($value, $data);

        $this->fromField->setValue($value['From'] ?? null);
        $this->toField->setValue($value['To'] ?? null);

        return $this;
    }

    public function FieldHolder($properties = []): DBHTMLText
    {
        $this->getFromField()->setAttribute('min', $this->min);
        $this->getToField()->setAttribute('max', $this->max);

        $this->getFromField()->setAttribute('placeholder', $this->min);
        $this->getToField()->setAttribute('placeholder', $this->max);

        return parent::FieldHolder($properties);
    }

    public function getFromField(): TextField
    {
        return $this->fromField;
    }

    public function getToField(): TextField
    {
        return $this->toField;
    }

    public function setMin(float $min): void
    {
        $this->min = $min;
    }

    public function setMax(float $max): void
    {
        $this->max = $max;
    }
}
