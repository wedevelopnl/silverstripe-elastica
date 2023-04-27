<?php

namespace WeDevelop\Elastica\Forms;

use SilverStripe\Forms\CheckboxSetField;
use WeDevelop\Elastica\Interfaces\FilterFieldInterface;
use WeDevelop\Elastica\Traits\FilterFieldTrait;

final class TermsFilterOptionSetField extends CheckboxSetField implements FilterFieldInterface
{
    use FilterFieldTrait;
}
