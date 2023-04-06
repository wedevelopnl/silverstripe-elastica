<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\Core\Environment;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use SilverStripe\Core\ClassInfo;

/**
 * @property FilterIndexPageItemExtension|SiteTree $owner
 * @mixin SiteTree
 */
final class FilterIndexPageItemExtension extends SiteTreeExtension implements IndexItemInterface
{
    use FilterIndexItemTrait;

    private const INDEX_SUFFIX = 'page';

    private ElasticaService $elasticaService;

    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = ElasticaService::singleton();
    }

    public function onAfterPublish(&$original): void
    {
        if ($this->owner->ShowInSearch) {
            $this->updateElasticaDocument();
            $this->updateElementsIndex($this->owner);
        } else {
            $this->deleteElasticaDocument();
            $this->deleteElementsIndex($this->owner);
        }
        $this->updateChildren($this->owner);
    }

    public function onAfterUnpublish(): void
    {
        $this->updateElementsIndex($this->owner);
        $this->updateChildren($this->owner);

        $this->deleteElasticaDocument();
    }

    private function updateChildren(SiteTree $page): void
    {
        foreach ($page->stageChildren(true) as $pageChild) {
            if ($pageChild->isPublished()) {
                $pageChild->updateElasticaDocument();
            } else {
                $pageChild->deleteElasticaDocument();
            }
            $this->updateElementsIndex($pageChild);
            $this->updateChildren($pageChild);
        }
    }

    private function updateElementsIndex(SiteTree $page): void
    {
        foreach ($page->findOwned() as $element) {
            $elementClass = get_class($element);
            if (in_array($elementClass, GridElementIndexExtension::getExtendedClasses(), true)) {
                $element->updateElasticaDocument();
            }
        }
    }

    protected function deleteElementsIndex($page)
    {
        /** @var DataObject $element */
        foreach ($page->findOwned() as $element) {
            $elementClass = get_class($element);
            if (in_array($elementClass, GridElementIndexExtension::getExtendedClasses())) {
                $element->deleteElasticaDocument();
            }
        }
    }

    public function updateElasticaDocument(): void
    {
        $this->elasticaService->setIndex(self::getIndexName())->add($this);
    }

    public function deleteElasticaDocument()
    {
        $this->elasticaService->setIndex(self::getIndexName())->delete($this);
    }


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
        $fields[ElasticaService::SUGGEST_FIELD_NAME] = [
            'type' => 'completion',
            'analyzer' => 'suggestion',
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateElasticaDocumentData(array &$data): void
    {
        $data['PageId'] = $this->owner->getElasticaId();
        $data['ParentID'] = $this->owner->ParentID;
        $data['Visible'] = $this->getPageVisibility($this->owner);
        $data['ShowInSearch'] = $this->owner->ShowInSearch;
        $data['Title'] = $this->owner->Title;
        $data['Content'] = $this->owner->Content;
        $data['Url'] = $this->cleanUrl($this->owner->getAbsoluteLiveLink(false));
        $data[ElasticaService::SUGGEST_FIELD_NAME] = $this->fillSuggest(['Title','Content'], $data);
    }

    public static function getIndexName(): string
    {
        $name =  sprintf('content-%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX'), self::INDEX_SUFFIX);

        if (Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX')) {
            $name = sprintf('%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX'), $name);
        }

        return $name;
    }

    public static function getExtendedClasses(): array
    {
        return array_filter(ClassInfo::subclassesFor(DataObject::class), function ($className) {
            return singleton($className)->hasExtension(self::class);
        });
    }
}
