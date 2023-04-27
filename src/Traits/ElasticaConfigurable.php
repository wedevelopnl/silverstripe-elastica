<?php

namespace WeDevelop\Elastica\Traits;

use SilverStripe\Core\ClassInfo;

trait ElasticaConfigurable
{
    private function getConfig(string $key): mixed
    {
        $class = ClassInfo::class_name($this->owner);

        $config = $class::config()->get('elastica');

        if (!is_array($config)) {
            return null;
        }

        if (!array_key_exists($key, $config)) {
            return null;
        }

        return $config[$key];
    }
}
