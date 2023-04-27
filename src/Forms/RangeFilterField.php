<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Traits\FilterFieldTrait;


final class RangeFilterField extends FormField implements FilterFieldInterface
{
    use FilterFieldTrait;

    private TextField $fromField;

    private TextField $toField;

    private float $min;

    private float $max;

    public function __construct($name, $title)
    {
        $this->fromField = TextField::create(
            $name . '[From]',
            _t(self::class . '.FROM_TITLE', 'From')
        );

        $this->toField = TextField::create(
            $name . '[To]',
            _t(self::class . '.TO_TITLE', 'To')
        );

        parent::__construct($name, $title);
    }

    /**
     * @param array<string, string>|DataObject $data
     */
    public function setValue($value, $data = null): self
    {
        parent::setValue($value, $data);

        if (is_array($value)) {
            $this->fromField->setValue((string)$value['From']);
            $this->toField->setValue((string)$value['To']);
        }

        return $this;
    }

    public function getFromField(): TextField
    {
        return $this->fromField;
    }

    public function getToField(): TextField
    {
        return $this->toField;
    }

    public function getMin(): float
    {
        return $this->min;
    }

    public function setMin(float $min): void
    {
        $this->min = $min;
    }

    public function getMax(): float
    {
        return $this->max;
    }

    public function setMax(float $max): void
    {
        $this->max = $max;
    }
}
