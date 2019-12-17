<?php

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Core\Injector\Injector;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;

/**
 * @property SiteTree $owner
 */
class FilterIndexPageItemExtension extends SiteTreeExtension
{
    use FilterIndexItemTrait;

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
        $this->elasticaService->add($this);
    }

    public function onAfterUnpublish()
    {
        $this->elasticaService->delete($this);
    }

    public function updateElasticaFields(&$fields)
    {
        $fields['ParentID'] = ['type' => 'integer'];
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
    }

    public function updateElasticaDocumentData(&$data)
    {
        $data['ParentID'] = $this->owner->ParentID;
        $data['Title'] = $this->owner->Title;
        $data['Content'] = $this->owner->Content;
    }
}
