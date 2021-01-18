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
        /** @var ElasticaService $elasticaService */
        $elasticaService = Injector::inst()->get('ElasticaService');
        $elasticaService->reindex();
    }
}
