<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Task;

use App\Pages\Jobs\JobPage;
use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\GlobalAggregation;
use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Elastica\Query\BoolQuery;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ViewableData;
use WeDevelop\Elastica\Extension\Writable;
use WeDevelop\Elastica\Filter\Filter;

final class ReindexTask extends BuildTask
{
    /** @config */
    private static string $segment = 'elastica-reindex';

    public function run($request): void
    {
        $classes = ClassInfo::subclassesFor(DataObject::class);
        $classes = array_filter($classes, function ($class) {
            $extensions = Config::forClass($class)->get('extensions', Config::UNINHERITED) ?? [];

            return in_array(Writable::class, $extensions);
        });

        printf('Found %d writables%s', count($classes), PHP_EOL);

        foreach ($classes as $class) {
            printf('Processing writable: %s%s', $class, PHP_EOL);

            try {
                /** @var DataObject|Writable $writable */
                $writable = singleton($class);
                $index = $writable->getElasticaIndex();
                $index->create(options: ['recreate' => true]);

                printf('Index created: %s%s', $index->getName(), PHP_EOL);

                $args = [];
                if (method_exists($writable, 'getElasticaSettings')) {
                    $args =  [
                        'settings' => $writable->getElasticaSettings()
                    ];
                }

                $index->create($args, options: ['recreate' => true]);

                $index->setMapping($writable->getElasticaMapping());

                printf('Mapping created%s', PHP_EOL);

                $documents = Versioned::withVersionedMode(function () use ($writable): array {
                    Versioned::set_stage(Versioned::LIVE);

                    return array_map(function (DataObject $dataObject) {
                        return $dataObject->getElasticaDocument();
                    }, $writable->getElasticaList()->toArray());
                });

                $index->addDocuments($documents);

                printf('%d documents added%s', count($documents), PHP_EOL);
            } catch (\Exception $exception) {
                printf('Failed: %s%s', $exception->getMessage(), PHP_EOL);
            }
        }
    }
}
