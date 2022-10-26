<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use SilverStripe\Core\ClassInfo;

final class FilterIndexDataObjectItemExtension extends DataExtension implements IndexItemInterface
{
    use FilterIndexItemTrait;

    private const INDEX_SUFFIX = 'object-item';

    private ElasticaService $elasticaService;

    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = ElasticaService::singleton();
    }

    public function onAfterWrite(): void
    {
        $this->elasticaService->setIndex(self::getIndexName())->add($this);
    }

    public function onAfterDelete(): void
    {
        $this->elasticaService->setIndex(self::getIndexName())->delete($this);
    }

    public static function getIndexName(): string
    {
        $name =  sprintf('filter-%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX'), self::INDEX_SUFFIX);

        if (Environment::getEnv('ELASTICSEARCH_INDEX_FILTER_PREFIX')) {
            $name = sprintf('%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX_FILTER_PREFIX'), $name);
        }

        return $name;
    }

    public static function getExtendedClasses(): array
    {
        return array_filter(ClassInfo::subclassesFor(DataObject::class), function ($className) {
            return singleton($className)->hasExtension(self::class);
        });
    }
}
