<?php

namespace TheWebmen\Elastica\Traits;

use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\SiteTree;

/**
 * @property FilterIndexItemTrait owner
 * @mixin DataObject
 */
trait FilterIndexItemTrait
{

    public function getElasticaFields()
    {
        $fields = [
            'ID' => ['type' => 'integer']
        ];

        if (method_exists($this->owner, 'updateElasticaFields')) {
            $this->owner->updateElasticaFields($fields);
        }

        $this->owner->extend('updateElasticaFields', $fields);

        return $fields;
    }

    public function getElasticaMapping()
    {
        $mapping = new \Elastica\Mapping();
        $mapping->setProperties($this->getElasticaFields());
        $mapping->setParam('date_detection', false);

        return $mapping;
    }

    public function getElasticaDocument()
    {
        $data = [
            'ID' => $this->owner->ID
        ];

        if (method_exists($this->owner, 'updateElasticaDocumentData')) {
            $this->owner->updateElasticaDocumentData($data);
        }

        $this->owner->extend('updateElasticaDocumentData', $data);

        return new \Elastica\Document($this->owner->getElasticaId(), $data, $this->owner->getIndexName());
    }

    public function getElasticaId()
    {
        return implode('_', [$this->owner->ClassName, $this->owner->ID]);
    }

    public function getElasticaPageId()
    {
        return implode('_', [Environment::getEnv('ELASTICSEARCH_INDEX'), $this->owner->getElasticaId()]);
    }

    public function getPageVisibility(SiteTree $page)
    {
        if (!$page->isPublished()) {
            return false;
        }

        if (!$page->getParent()) {
            return true;
        }

        return $this->getPageVisibility($page->getParent());
    }

    private function cleanUrl(string $url): string
    {
        return explode('?', $url)[0];
    }
}
