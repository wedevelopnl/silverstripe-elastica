<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Forms;

use SilverStripe\Forms\OptionsetField;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Traits\FilterFieldTrait;

final class DateFilterField extends OptionsetField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
