<?php

namespace TheWebmen\Elastica\Tasks;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use TheWebmen\Elastica\Services\ElasticaService;

class ElasticaReindexTask extends BuildTask
{
    private static $segment = 'elastica-reindex';
    
    public function run($request)
    {
        /** @var ElasticaService $elasticaService */
        $elasticaService = Injector::inst()->get('ElasticaService');
        $elasticaService->reindex();
    }
}
