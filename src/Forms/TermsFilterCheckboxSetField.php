<?php

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\CheckboxSetField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

class TermsFilterCheckboxSetField extends CheckboxSetField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
