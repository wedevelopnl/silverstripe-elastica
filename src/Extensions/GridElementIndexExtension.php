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

class GridElementIndexExtension extends DataExtension implements IndexItemInterface
{

    use FilterIndexItemTrait;

    const INDEX_SUFFIX = 'grid-element';

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
        $fields['PageId'] = ['type' => 'keyword'];
        $fields['ElementTitle'] = ['type' => 'text'];
        $fields['Content'] = ['type' => 'text'];
        $fields['Title'] = ['type' => 'text'];
        $fields['Url'] = [
            'type' => 'text',
            'fielddata' => true
        ];

    }

    public function updateElasticaDocumentData(&$data)
    {
        $page = $this->owner->getPage();

        $data['PageId'] = $page?implode('_', [$page->ClassName, $page->ID]):'none';
        $data['ElementTitle'] = $this->owner->getTitle();

        if ($this->owner->hasField('Content')) {
            $data['Content'] = $this->owner->Content;
        }
        
        if ($page) {
            $data['Url'] = $page->AbsoluteLink();
            $data['Title'] = $page->getTitle();
        }
    }


    public function onAfterPublish()
    {
        $this->elasticaService->setIndex(self::getIndexName())->add($this);
    }

    public function onAfterUnpublish()
    {
        $this->elasticaService->setIndex(self::getIndexName())->delete($this);
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
