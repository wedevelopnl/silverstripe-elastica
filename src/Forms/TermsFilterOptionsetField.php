<?php

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\OptionsetField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

class TermsFilterOptionsetField extends OptionsetField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
