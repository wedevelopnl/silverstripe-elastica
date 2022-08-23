<?php
namespace TheWebmen\Elastica\Extensions;

use SilverStripe\ORM\DataExtension;
use TheWebmen\Elastica\Extensions\FilterIndexDataObjectItemExtension;
use TheWebmen\Elastica\Services\ElasticaService;
use SilverStripe\Core\Injector\Injector;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;
use SilverStripe\Core\Environment;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;

class DataObjectIndexExtension extends DataExtension implements IndexItemInterface
{
    use FilterIndexItemTrait;

    const INDEX_SUFFIX = 'data-object';

    /**
     * @var ElasticaService
     */
    private $elasticaService;
    
    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = Injector::inst()->get('ElasticaService');
    }

    public function updateElasticaFields(&$fields)
    {
        $fields['Title'] = ['type' => 'text'];
    }

    public function updateElasticaDocumentData(&$data)
    {
        $data['Title'] = $this->owner->Title;
    }

    public function onAfterPublish()
    {
        $this->updateElasticaDocument();
    }

    public function onAfterUnpublish()
    {
        $this->elasticaService->setIndex(self::getIndexName())->delete($this);
    }

    public function onBeforeDelete()
    {
        $this->onAfterUnpublish();
    }

    public function updateElasticaDocument()
    {
        $this->elasticaService->setIndex(self::getIndexName())->add($this);
    }

    public static function getIndexName()
    {
        $name =  sprintf('content-%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX'), self::INDEX_SUFFIX);

        if (Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX')) {
            $name = sprintf('%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX'), $name);
        }

        return $name;
    }

    public static function getExtendedClasses()
    {
        $classes = [];
        $candidates = ClassInfo::subclassesFor(DataObject::class);
        foreach ($candidates as $candidate) {
            if (singleton($candidate)->hasExtension(self::class)) {
                $classes[] = $candidate;
            }
        }
        return $classes;
    }
}
