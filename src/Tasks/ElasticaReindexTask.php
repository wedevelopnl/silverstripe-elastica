<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Tasks;

use SilverStripe\Dev\BuildTask;
use TheWebmen\Elastica\Services\ElasticaService;

final class ElasticaReindexTask extends BuildTask
{
    /** @config */
    private static string $segment = 'elastica-reindex';

    public function run($request): void
    {
        $elasticaService = ElasticaService::singleton();
        $elasticaService->reindex();
    }
}
