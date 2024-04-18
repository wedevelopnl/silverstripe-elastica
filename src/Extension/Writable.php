<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Extension;

use Elastica\Document;
use Elastica\Index;
use Elastica\Mapping;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\SS_List;

class Writable extends Extension
{
    public function getElasticaIndex(): Index
    {
        return $this->getOwner()->getElasticaIndex();
    }

    public function getElasticaMapping(): Mapping
    {
        return $this->getOwner()->getElasticaMapping();
    }

    public function getElasticaList(): SS_List
    {
        return $this->getOwner()->getElasticaList();
    }

    public function getElasticaId(): string
    {
        return sprintf('%s_%s', $this->getOwner()->ClassName, $this->getOwner()->ID);
    }

    public function getElasticaDocument(): Document
    {
        return $this->getOwner()->getElasticaDocument();
    }

    public function onAfterPublish(): void
    {
        $this->getElasticaIndex()->addDocument($this->getElasticaDocument());
    }

    public function onAfterUnpublish(): void
    {
        $this->getElasticaIndex()->deleteById($this->getElasticaDocument()->getId());
    }
}
