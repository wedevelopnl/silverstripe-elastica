<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Traits;

use SilverStripe\CMS\Model\SiteTree;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;

/**
 * @property IndexItemInterface $owner
 */
trait FilterIndexItemTrait
{
    public function getElasticaFields(): array
    {
        $fields = [
            'ID' => ['type' => 'integer'],
        ];

        $this->owner->extend('updateElasticaFields', $fields);

        if (method_exists($this->owner, 'updateElasticaFields')) {
            $this->owner->updateElasticaFields($fields);
        }

        return $fields;
    }

    public function getElasticaMapping(): \Elastica\Type\Mapping
    {
        $mapping = new \Elastica\Type\Mapping();
        $mapping->setProperties($this->getElasticaFields());
        $mapping->setParam('date_detection', false);

        return $mapping;
    }

    public function getElasticaId(): string
    {
        return implode('_', [$this->owner->ClassName, $this->owner->ID]);
    }

    public function getElasticaDocument(): \Elastica\Document
    {
        $data = [
            'ID' => $this->owner->ID,
        ];

        $this->owner->extend('updateElasticaDocumentData', $data);

        if (method_exists($this->owner, 'updateElasticaDocumentData')) {
            $this->owner->updateElasticaDocumentData($data);
        }

        return new \Elastica\Document($this->owner->getElasticaId(), $data, $this->owner->getIndexName());
    }

    public function getPageVisibility(SiteTree $page): bool
    {
        if (!$page->isPublished()) {
            return false;
        }

        if (!$page->getParent() instanceof SiteTree) {
            return true;
        }

        return $this->getPageVisibility($page->getParent());
    }

    private function cleanUrl(string $url): string
    {
        return explode('?', $url)[0];
    }
}
