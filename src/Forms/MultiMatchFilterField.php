<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Forms\TextField;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class MultiMatchFilterField extends TextField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
