<?php

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use SilverStripe\Core\ClassInfo;

class FilterIndexDataObjectItemExtension extends DataExtension implements IndexItemInterface
{
    use FilterIndexItemTrait;

    const INDEX_SUFFIX = 'object-item';

    /**
     * @var ElasticaService
     */
    private $elasticaService;

    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = Injector::inst()->get('ElasticaService');
    }

    public function onAfterWrite()
    {
        $this->elasticaService->add($this);
    }

    public function onAfterDelete()
    {
        $this->elasticaService->delete($this);
    }

    public static function getIndexName()
    {
        $name =  sprintf('filter-%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX'), self::INDEX_SUFFIX);

        if (Environment::getEnv('ELASTICSEARCH_INDEX_FILTER_PREFIX')) {
            $name = sprintf('%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX_FILTER_PREFIX'), $name);
        }
        return $name;
    }

    public static function getExtendedClasses()
    {
        $classes = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $candidate) {
            if (singleton($candidate)->hasExtension(self::class)) {
                $classes[] = $candidate;
            }
        }
        return $classes;
    }
}
