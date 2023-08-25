<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter\RangeFilter;

use SilverStripe\ORM\DataObject;
use WeDevelop\Elastica\Filter\RangeFilter;

class Option extends DataObject
{
    /** @config */
    private static string $singular_name = 'Option';

    /** @config */
    private static string $table_name = 'WeDevelop_Elastica_RangeFilter_Option';

    /** @config */
    private static array $db = [
        'Value' => 'Varchar',
        'Label' => 'Varchar',
        'Sort' => 'Int',
    ];

    /** @config */
    private static array $has_one = [
        'Filter' => RangeFilter::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'Value',
        'Label',
    ];
}
