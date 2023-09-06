<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Extension;

use Elastica\Client;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

class Readable extends Extension
{
    /** @config */
    private static array $elastica_indices = [];

    public static function available_fields(string $readable): array
    {
        /** @var Client $client */
        $client = Injector::inst()->get(Client::class);
        $indices = Config::inst()->get($readable, 'elastica_indices');

        $fields = [];

        foreach ($indices as $index) {
            $properties = $client->getIndex($index)->getMapping()['properties'] ?? null;

            if (!$properties) {
                continue;
            }

            $fields = \array_merge($fields, self::available_fields_from_properties($properties));

        }

        return $fields;
    }

    public static function available_fields_from_properties(array $properties, $path = null): array
    {
        $fields = [];

        foreach ($properties as $key => $property) {
            $propertyPath = $path . $key;
            $fields[] = $propertyPath;

            if (isset($property['fields'])) {
                $keys = \array_keys($property['fields']);
                $keys = \preg_filter('/^/', $propertyPath . '.', $keys);
                $fields = \array_merge($fields, $keys);
            }

            if (isset($property['properties'])) {
                $fields = \array_merge($fields, self::available_fields_from_properties($property['properties'], $propertyPath . '.'));
            }
        }

        return $fields;
    }
}
