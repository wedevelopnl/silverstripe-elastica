<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Form;

use SilverStripe\Forms\DropdownField;

class RangeDropdownField extends DropdownField
{
    public function __construct(string $name, string $title = null, array $source = [])
    {
        parent::__construct($name, $title, $source);
        
        $this->setEmptyString(_t(__CLASS__ . '.EMPTY_STRING', '- Choose an option -'));
    }
}
