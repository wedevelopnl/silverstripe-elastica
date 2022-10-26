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

        if (method_exists($this->owner, 'updateElasticaFields')) {
            $this->owner->updateElasticaFields($fields);
        }

        $this->owner->extend('updateElasticaFields', $fields);

        return $fields;
    }

    public function getElasticaMapping(): \Elastica\Mapping
    {
        $mapping = new \Elastica\Mapping();
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

    /**
     * @param string[] $fields
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function fillSuggest(array $fields, array $data): array
    {
        $analyzed = [];

        foreach ($fields as $field) {
            $text = $data[$field] ?? '';

            if (empty($text)) {
                continue;
            }

            $words = array_column($this->elasticaService->getIndex()->analyze(['analyzer' => 'suggestion', 'text' => $text]), 'token');
            $analyzed = array_merge($words, $analyzed);
        }

        $analyzed = array_values(array_unique($analyzed));

        return ['input' => $analyzed];
    }
}
