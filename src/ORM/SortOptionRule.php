<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\ORM;

use App\ORM\SortOption;
use SilverStripe\ORM\DataObject;

class SortOptionRule extends DataObject
{
    /** @config */
    private static array $db = [
        'FieldName' => 'Varchar',
        'Direction' => "Enum('asc,desc')",
        'Sort' => 'Int',
    ];

    /** @config */
    private static array $has_one = [
        'Option' => SortOption::class,
    ];

    private static string $default_sort = '"Sort" ASC';

    public function toArray(): array
    {
        return [
            $this->FieldName => $this->Direction,
        ];
    }
}
