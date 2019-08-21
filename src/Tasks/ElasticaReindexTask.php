<?php

namespace TheWebmen\Elastica\Tasks;

use SilverStripe\Dev\BuildTask;
use TheWebmen\Elastica\Services\ElasticaService;

class ElasticaReindexTask extends BuildTask
{
    private static $segment = 'elastica-reindex';
    
    public function run($request)
    {
        ElasticaService::singleton()->reindex();
    }
}
