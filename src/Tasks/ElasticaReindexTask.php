<?php

namespace TheWebmen\Elastica\Tasks;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use TheWebmen\Elastica\Extensions\FilterIndexDataObjectItemExtension;
use TheWebmen\Elastica\Extensions\FilterIndexPageItemExtension;
use TheWebmen\Elastica\Extensions\GridElementIndexExtension;
use TheWebmen\Elastica\Services\ElasticaService;
use SilverStripe\Core\Environment;
class ElasticaReindexTask extends BuildTask
{
    private static $segment = 'elastica-reindex';

    public function run($request)
    {

        $esConfig = [
            'host' => Environment::getEnv('ELASTICSEARCH_HOST'),
            'port' => Environment::getEnv('ELASTICSEARCH_PORT')
        ];

        $indexers = [
            FilterIndexPageItemExtension::class,
            FilterIndexDataObjectItemExtension::class,
            GridElementIndexExtension::class
        ];

        foreach ($indexers as $indexer) {
            $indexName = call_user_func(sprintf('%s::%s', $indexer, 'getIndexName'));

            /** @var ElasticaService $elasticaService */
            $elasticaService = new ElasticaService($indexName,$esConfig);
            $elasticaService->reindex();
        }

    }
}
