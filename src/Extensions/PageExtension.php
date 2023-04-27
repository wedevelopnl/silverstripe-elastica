<?php

namespace WeDevelop\Elastica\Extensions;

use Elastica\Query;
use Elastica\Query\MatchQuery;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use WeDevelop\Elastica\Services\ElasticaService;
use WeDevelop\Elastica\Traits\Configurable;
use WeDevelop\Elastica\Traits\ElasticaConfigurable;

class PageExtension extends Extension
{
    use ElasticaConfigurable;
    /**
     * @param array<string, array<string, mixed>> $fields
     */
    public function updateElasticaFields(array &$fields): void
    {
        $fields['ParentID'] = ['type' => 'integer'];
        $fields['PageId'] = ['type' => 'keyword'];
        $fields['Visible'] = ['type' => 'boolean'];
        $fields['Title'] = [
            'type' => 'text',
            'fielddata' => true,
            'fields' => [
                'completion' => [
                    'type' => 'completion',
                ],
            ],
        ];
        $fields['Content'] = ['type' => 'text'];
        $fields['Url'] = ['type' => 'text'];

        if ($this->getConfig('include_grid_elements')) {
            $fields['GridContent'] = ['type' => 'text'];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateElasticaDocumentData(array &$data): void
    {
        $data['PageId'] = $this->owner->ID;
        $data['ParentID'] = $this->owner->ParentID;
        $data['Visible'] = $this->getPageVisibility($this->owner);
        $data['Title'] = $this->owner->Title;
        $data['Content'] = $this->owner->Content;
        $data['Url'] = $this->owner->getAbsoluteLiveLink(false) ?: $this->owner->Link();

        if ($this->getConfig('include_grid_elements')) {
            $data['GridContent'] = $elementsForSearch = $this->owner->getElementsForSearch();
        }
    }

    private function cleanUrl(string $url): string
    {
        return explode('?', $url)[0];
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

    public function updateFullTextBoolQuery(Query\BoolQuery &$bool): void
    {
        $visibleQuery = new MatchQuery('Visible', true);
        $bool->addMust($visibleQuery);
    }

    public function onAfterPublish(&$original): void
    {
        $this->getSearchableInstance()->updateElasticaDocument();
    }

    public function onAfterUnpublish(): void
    {
        $this->getSearchableInstance()->updateElasticaDocument();
    }

    private function getSearchableInstance(): ?SearchableObjectExtension
    {
        if (!$this->getOwner()->hasExtension(SearchableObjectExtension::class)) {
            return null;
        }

        $searchableInstance = $this->getOwner()->getExtensionInstance(SearchableObjectExtension::class);
        $searchableInstance->setOwner($this->owner);

        return $searchableInstance;
    }
}
