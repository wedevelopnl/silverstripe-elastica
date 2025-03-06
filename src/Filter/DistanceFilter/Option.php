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
        'IsDefault' => 'Boolean',
    ];

    /** @config */
    private static array $has_one = [
        'Filter' => DistanceFilter::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'Distance',
        'Label',
        'IsDefault',
    ];

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->isChanged('IsDefault') && $this->getField('IsDefault')) {
            // This has some weird logic to work around the fact that we can't have one radio option set in
            // GridFieldEditableColumns. We're making this option the default and disabling it on all other options
            // that are linked to this filter that are also not this option.
            self::get()
                ->filter(['IsDefault' => true, 'FilterID' => $this->FilterID])
                ->exclude(['ID' => $this->ID])
                ->each(static function (Option $option) {
                    $option->setField('IsDefault', false);
                    $option->write();
                });
        }
    }
}
