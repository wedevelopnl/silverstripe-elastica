<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Tasks;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use WeDevelop\Elastica\Services\ElasticaService;

final class ElasticaReindexTask extends BuildTask
{
    /** @config */
    private static string $segment = 'elastica-reindex';

    public function run($request): void
    {

        if (!ClassInfo::exists('TractorCow\Fluent\State\FluentState') ||
        !\TractorCow\Fluent\Model\Locale::get()->count()) {
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
