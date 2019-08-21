<?php

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\DropdownField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

class TermsFilterDropdownField extends DropdownField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
