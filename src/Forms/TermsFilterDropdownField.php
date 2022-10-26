<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\DropdownField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class TermsFilterDropdownField extends DropdownField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
