<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Extension;

use SilverStripe\ORM\DataExtension;

class Writeable extends DataExtension
{
    /** @config */
    private static ?string $elastica_index = null;
}
