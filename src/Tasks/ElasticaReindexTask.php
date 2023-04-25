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
        if (!class_exists('TractorCow\Fluent\State\FluentState')) {
            $elasticaService = ElasticaService::singleton();
            $elasticaService->reindex();
            return;
        }

        foreach (\TractorCow\Fluent\Model\Locale::get() as $locale) {
            \TractorCow\Fluent\State\FluentState::singleton()
                ->withState(static function (\TractorCow\Fluent\State\FluentState $state) use ($locale) {
                    $state->setLocale($locale->Locale);

                    $elastica = ElasticaService::singleton();
                    $elastica->reindex();
                });
        }
    }
}
