<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\OptionsetField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class TermsFilterOptionsetField extends OptionsetField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
