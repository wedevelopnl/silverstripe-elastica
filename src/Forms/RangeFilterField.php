<?php

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

class RangeFilterField extends FormField implements FilterFieldInterface
{
    use FilterFieldTrait;

    protected $fromField = null;

    protected $toField = null;

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

    public function setValue($value, $data = null)
    {
        parent::setValue($value, $data);

        if (is_array($value)) {
            $this->fromField->setValue($value['From']);
            $this->toField->setValue($value['To']);
        }

        return $this;
    }

    public function getFromField()
    {
        return $this->fromField;
    }

    public function getToField()
    {
        return $this->toField;
    }
}
