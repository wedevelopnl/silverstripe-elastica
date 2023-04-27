<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Forms;

use SilverStripe\Forms\DropdownField;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Traits\FilterFieldTrait;

final class TermsFilterDropdownField extends DropdownField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
