<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\CheckboxSetField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class TermsFilterCheckboxSetField extends CheckboxSetField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
