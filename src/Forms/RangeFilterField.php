<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class RangeFilterField extends FormField implements FilterFieldInterface
{
    use FilterFieldTrait;

    private TextField $fromField;

    private TextField $toField;

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
            $this->fromField->setValue($value['From']);
            $this->toField->setValue($value['To']);
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
}
