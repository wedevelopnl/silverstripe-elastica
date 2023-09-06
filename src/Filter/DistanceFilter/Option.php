<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter\DistanceFilter;

use SilverStripe\ORM\DataObject;
use WeDevelop\Elastica\Filter\DistanceFilter;

class Option extends DataObject
{
    /** @config */
    private static string $singular_name = 'Option';

    /** @config */
    private static string $table_name = 'WeDevelop_Elastica_DistanceFilter_Option';

    /** @config */
    private static array $db = [
        'Distance' => 'Varchar',
        'Label' => 'Varchar',
        'Sort' => 'Int',
    ];

    /** @config */
    private static array $has_one = [
        'Filter' => DistanceFilter::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'Distance',
        'Label',
    ];
}
