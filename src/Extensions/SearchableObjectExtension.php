<?php

namespace WeDevelop\Elastica\Extensions;

use App\Page\TripPage;
use Elastica\Document;
use Elastica\Mapping;
use Elastica\Query;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use WeDevelop\Elastica\Services\ElasticaService;
use WeDevelop\Elastica\Traits\Configurable;
use WeDevelop\Elastica\Traits\ElasticaConfigurable;
use function Deployer\Support\array_merge_alternate;

class SearchableObjectExtension extends Extension
{
    use Extensible;
    use ElasticaConfigurable;

    private const DEFAULT_PAGE_LIK_SHOW_OFFSET_SIZE = 1;
    private const DEFAULT_PAGE_SIZE = 10;
    private const DEFAULT_PAGE_VAR = 'page';

    /**
     * @return array<int, string>
     */
    public function getIndexNames(): array
    {
        $indexNames = $this->getConfig('index_names') ?? [];

        if (method_exists($this->owner, 'updateElasticaIndexNames')) {
            $this->owner->updateElasticaIndexNames($indexNames);
        }
        $this->extend('updateElasticaIndexNames', $indexNames);

        return $indexNames;
    }

    public function getStringifiedIndexName(): string
    {
        $indexNames = $this->getIndexNames();
        if (empty($indexNames)) {
            return '';
        }

        if (count($indexNames) > 1) {
            return implode(',', $indexNames);
        }

        return $indexNames[0];
    }

    /**
     * @return array<int, mixed>
     */
    public function getElasticaFields(): array
    {
        $fields = $this->getConfig('fields') ?? [];

        if (method_exists($this->owner, 'updateElasticaFields')) {
            $this->owner->updateElasticaFields($fields);
        }

        $this->owner->extend('updateElasticaFields', $fields);

        return $fields;
    }

    public function getElasticaMapping(): Mapping
    {
        $mapping = new Mapping();
        $mapping->setProperties($this->getElasticaFields());
        $mapping->setParam('date_detection', false);

        $this->extend('updateElasticaMapping', $mapping);

        return $mapping;
    }

    public function getElasticaSettings()
    {
        $settings = [
            'analysis' => [
                'filter' => [
                    'dutch_stop' => [
                        'type' => 'stop',
                        'stopwords' => '_dutch_',
                        'ignore_case' => true,
                    ],
                    'filename_stop' => [
                        'type' => 'stop',
                        'stopwords' => ['doc', 'jpg', 'jpeg', 'png', 'pdf', 'exe', 'csv'],
                    ],
                    'length' => [
                        'type' => 'length',
                        'min' => 3,
                    ],
                ],
                'char_filter' => [
                    'html' => [
                        'type' => 'html_strip',
                    ],
                    'number_filter' => [
                        'type' => 'pattern_replace',
                        'pattern' => '\\d+',
                        'replacement' => '',
                    ],
                    'file_filter' => [
                        'type' => 'pattern_replace',
                        'pattern' => '^[\\w\\-]+\\.[a-z]{1,4}$',
                        'replacement' => '',
                    ],
                ],
                'analyzer' => [
                    'suggestion' => [
                        'tokenizer' => 'standard',
                        'filter' => ['dutch_stop', 'lowercase', 'filename_stop', 'length'],
                        'char_filter' => ['html', 'number_filter', 'file_filter'],
                    ],
                ],
            ],
        ];

        if (method_exists($this->owner, 'updateElasticaSettings')) {
            $this->owner->updateElasticaSettings($settings);
        }
        $this->owner->extend('updateElasticaSettings', $settings);

        return $settings;
    }

    public function buildFullTextSaerchQuery(?string $search): Query
    {
        $query = new Query();
        $bool = new \Elastica\Query\BoolQuery();

        $queryConfig = $this->getConfig('query');

        if (!$queryConfig) {
            // use default search - multimatch on all defined text fields
            $fields = array_filter($this->getElasticaFields(), function($field) {
                return $field['type'] == 'text';
            });
            $multiMatchQuery = new Query\MultiMatch();
            $multiMatchQuery->setFields(array_keys($fields));
            $multiMatchQuery->setQuery($search);

            $bool->addMust($multiMatchQuery);
        }

        if (method_exists($this->owner, 'updateFullTextBoolQuery')) {
            $this->owner->updateBuildQuery($bool);
        }
        $this->owner->extend('updateFullTextBoolQuery', $bool);

        $query->setQuery($bool);

        return $query;
    }

    public static function createInstance(string $class): self
    {
        if ($class::has_extension(self::class)) {
            $instance = $class::singleton();
            $extension = $instance->getExtensionInstance(self::class);
            $extension->setOwner($instance);

            return $extension;
        }
    }

    public function getPageSize(): int
    {
        return $this->getConfig('page_size') ?? self::DEFAULT_PAGE_SIZE;
    }

    public function getPageLinkShowOffsetSize(): int
    {
        return $this->getConfig('page_link_show_offset_size') ?? self::DEFAULT_PAGE_LIK_SHOW_OFFSET_SIZE;
    }

    public function getPageVar(): string
    {
        return $this->getConfig('page_var') ?? self::DEFAULT_PAGE_VAR;
    }

    public function isReadOnly(): bool
    {
        $readOnly = $this->getConfig('read_only');

        return $readOnly === true;
    }

    public function getElasticaDocument(): Document
    {
        $data = [
            'ID' => $this->owner->ID
        ];

        if (method_exists($this->owner, 'updateElasticaDocumentData')) {
            $this->owner->updateElasticaDocumentData($data);
        }

        $this->owner->extend('updateElasticaDocumentData', $data);

        return new Document($this->getElasticaId(), $data, $this->getIndexNames()[0]);
    }

    private function getElasticaId(): string
    {
        $prefix = $this->getConfig('document_name_prefix');

        if (!$prefix) {
            $prefix = $this->owner->ClassName;
        }

        return implode('_', [$prefix, $this->owner->ID]);
    }

    public static function getExtendedClasses($readOnly = null): array
    {
        return array_filter(ClassInfo::subclassesFor(DataObject::class), function ($className) use ($readOnly) {

            if (!$className::has_extension(self::class)) {
                return false;
            };

            if (!$config = $className::config()->get('elastica')) {
                return false;
            }

            $hasIndex = isset($config['index_names']) && !empty($config['index_names']);

            if (!$hasIndex) {
                return false;
            }

            if (is_null($readOnly)) {
                return true;
            }

            $classIndexIsReadOnly = isset($config['read_only']) && $config['read_only'] === true;

            return $classIndexIsReadOnly === $readOnly;
        });
    }

    public function updateElasticaDocument(): void
    {
        if ($this->isReadOnly()) {
            return;
        }

        /** @var ElasticaService $elasticaService */
        $elasticaService = Injector::inst()->get('ElasticaService');
        $elasticaService->setIndex($this->getIndexNames()[0])->add($this);
    }
}
