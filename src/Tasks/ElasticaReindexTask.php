<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use TheWebmen\Elastica\Services\ElasticaService;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

final class ElasticaReindexTask extends BuildTask
{
    /** @config */
    private static string $segment = 'elastica-reindex';

    public function run($request): void
    {
        if (ClassInfo::exists(FluentState::class)) {
            $this->doRunFluent();
        }
        else {
            ElasticaService::singleton()->reindex();
        }
    }

    private function doRunFluent(): void {
        foreach (Locale::get() as $locale) {
            FluentState::singleton()
                ->withState(static function (FluentState $state) use ($locale) {
                    $state->setLocale($locale->Locale);
                    ElasticaService::singleton()->reindex();
                });
        }
    }
}
