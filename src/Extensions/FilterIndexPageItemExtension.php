<?php

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\Core\Environment;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Core\Injector\Injector;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use SilverStripe\Core\ClassInfo;
/**
 * @property SiteTree $owner
 */
class FilterIndexPageItemExtension extends SiteTreeExtension implements IndexItemInterface
{
    use FilterIndexItemTrait;

    const INDEX_SUFFIX = 'page';

    /**
     * @var ElasticaService
     */
    private $elasticaService;

    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = Injector::inst()->get('ElasticaService');

    }

    public function onAfterPublish(&$original)
    {
        $this->elasticaService->setIndex(self::getIndexName())->add($this);
    }

    public function onAfterUnpublish()
    {
        $this->elasticaService->setIndex(self::getIndexName())->delete($this);
    }

    public function updateElasticaFields(&$fields)
    {
        $fields['ParentID'] = ['type' => 'integer'];
        $fields['PageId'] = ['type' => 'keyword'];
        $fields['Title'] = [
            'type' => 'text',
            'fielddata' => true,
            'fields' => [
                'completion' => [
                    'type' => 'completion'
                ]
            ]
        ];
        $fields['Content'] = ['type' => 'text'];
        $fields['Url'] = ['type' => 'text'];
    }

    public function updateElasticaDocumentData(&$data)
    {
        $data['PageId'] = $this->owner->getElasticaPageId();
        $data['ParentID'] = $this->owner->ParentID;
        $data['Title'] = $this->owner->Title;
        $data['Content'] = $this->owner->Content;
        $data['Url'] = $this->owner->AbsoluteLink();
    }


    public static function getIndexName()
    {
        $name =  sprintf('content-%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX'), self::INDEX_SUFFIX);

        if (Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX')) {
            $name = sprintf('%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX'), $name);
        }

        return $name;
    }

    public static function  getExtendedClasses()
    {
        $classes = [];
        foreach (ClassInfo::subclassesFor(SiteTree::class) as $candidate) {
            if (singleton($candidate)->hasExtension(self::class)) {
                $classes[] = $candidate;
            }
        }
        return $classes;
    }
}
