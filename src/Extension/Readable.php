<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Extension;

use Elastica\Client;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

class Readable extends Extension
{
    /** @config */
    private static array $elastica_indices = [];

    public function getElasticaFields(): array
    {
        $indices = $this->getOwner()->config()->get('elastica_indices');
        /** @var Client $client */
        $client = Injector::inst()->get(Client::class);

        $mapping = [];

        foreach ($indices as $index) {
            $mapping = array_merge($mapping, $client->getIndex($index)->getMapping()['properties'] ?: []);
        }

        return array_keys($mapping);
    }
}
