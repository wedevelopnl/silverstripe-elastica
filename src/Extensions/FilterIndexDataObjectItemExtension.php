<?php

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;

class FilterIndexDataObjectItemExtension extends DataExtension
{
    use FilterIndexItemTrait;

    /**
     * @var ElasticaService
     */
    private $elasticaService;

    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = ElasticaService::singleton();
    }

    public function onAfterWrite()
    {
        $this->elasticaService->add($this);
    }

    public function onAfterDelete()
    {
        $this->elasticaService->delete($this);
    }
}
