<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;

/**
 * @property BaseElement $owner
 */
final class GridElementIndexExtension extends DataExtension implements IndexItemInterface
{
    use FilterIndexItemTrait;

    private const INDEX_SUFFIX = 'grid-element';

    private ElasticaService $elasticaService;

    public function __construct()
    {
        parent::__construct();

        $this->elasticaService = ElasticaService::singleton();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateElasticaFields(array &$fields): void
    {
        $fields['PageId'] = ['type' => 'keyword'];
        $fields['Visible'] = ['type' => 'boolean'];
        $fields['ElementTitle'] = ['type' => 'string'];
        $fields['Content'] = ['type' => 'string'];
        $fields['Title'] = ['type' => 'string'];
        $fields['Url'] = [
            'type' => 'string',
            'fielddata' => true,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateElasticaDocumentData(array &$data): void
    {
        /** @var SiteTree|null $page */
        $page = $this->owner->getPage();

        if ($page instanceof SiteTree && $page->hasExtension(FilterIndexPageItemExtension::class)) {
            $data['Visible'] = $this->getPageVisibility($page);
            $data['Url'] = $this->cleanUrl($page->getAbsoluteLiveLink(false));
            $data['Title'] = $page->getTitle();
            /** @var FilterIndexPageItemExtension $page */
            $data['PageId'] = $page->getElasticaId();
            $data['ShowInSearch'] = $page->ShowInSearch;
        } else {
            $data['PageId'] = 'none';
            $data['Visible'] = false;

            $data['ShowInSearch'] = false;
        }

        $data['ElementTitle'] = $this->owner->getTitle();

        if (isset($this->owner->Content)) {
            $data['Content'] = $this->owner->Content;
        }
    }

    public function onAfterPublish(): void
    {
        if ($this->owner->getPage()->ShowInSearch) {
            $this->updateElasticaDocument();
        } else {
            $this->deleteElasticaDocument();
        }

    }

    public function onAfterUnpublish(): void
    {
        $this->deleteElasticaDocument();
    }

    public function onBeforeDelete(): void
    {
        $this->deleteElasticaDocument();
    }

    public function updateElasticaDocument(): void
    {
        $this->elasticaService->setIndex(self::getIndexName())->add($this);
    }

    public function deleteElasticaDocument(): void
    {
        $this->elasticaService->setIndex(self::getIndexName())->delete($this);
    }

    public static function getIndexName(): string
    {
        $name = sprintf('content-%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX'), self::INDEX_SUFFIX);

        if (Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX')) {
            $name = sprintf('%s-%s', Environment::getEnv('ELASTICSEARCH_INDEX_CONTENT_PREFIX'), $name);
        }

        return $name;
    }

    public static function getExtendedClasses(): array
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
