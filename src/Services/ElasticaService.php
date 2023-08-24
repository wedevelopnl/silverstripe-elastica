<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Services;

use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Elastica\Query;
use Elastica\ResultSet;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Versioned\Versioned;
use TheWebmen\Elastica\Extensions\FilterIndexDataObjectItemExtension;
use TheWebmen\Elastica\Extensions\FilterIndexPageItemExtension;
use TheWebmen\Elastica\Extensions\GridElementIndexExtension;
use TheWebmen\Elastica\Interfaces\IndexItemInterface;
use TractorCow\Fluent\State\FluentState;

final class ElasticaService
{
    use Extensible;
    use Injectable;
    use Configurable;

    /** @config */
    private static int $number_of_shards = 1;

    /** @config */
    private static int $number_of_replicas = 1;

    private Client $client;

    private Index $index;

    /**
     * @param array<string, string> $config
     */
    public function __construct(array $config = [])
    {
        $this->client = new Client($config);
        $this->setIndex(FilterIndexPageItemExtension::getIndexName());
    }

    public function setIndex(string $indexName): self
    {
        $this->extend('updateIndexName', $indexName);

        if (ClassInfo::exists(FluentState::class)) {
            $locale = strtolower(FluentState::singleton()->getLocale());

            if (!str_contains($indexName, $locale)) {
                $indexName .= '-' . strtolower($locale);
            }
        }

        $this->index = $this->client->getIndex($indexName);

        return $this;
    }

    public function add(IndexItemInterface $record): void
    {
        $this->index->addDocument($record->getElasticaDocument());
    }

    public function delete(IndexItemInterface $record): void
    {
        $this->index->deleteById($record->getElasticaId());
    }

    public function reindex(): void
    {
        Versioned::set_reading_mode(Versioned::LIVE);

        foreach ($this->getIndexClasses() as $indexer) {
            $indexName = call_user_func(sprintf('%s::%s', $indexer, 'getIndexName'));

            $this->setIndex($indexName);
            echo "Create index {$this->getIndex()->getName()} \n";

            $this->index->create([
                'settings' => [
                    'number_of_shards' => self::config()->get('number_of_shards'),
                    'number_of_replicas' => self::config()->get('number_of_replicas'),
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
                    ],
                ],
            ], true);
            echo "Done\n";

            $documents = $this->indexDocuments($indexer);

            $this->extend('updateReindexDocuments', $documents);

            $removeDocuments = $this->filterNotSearchable($documents);

            echo "Add documents\n";
            if (count($documents) > 0) {
                $this->index->addDocuments($documents);
            }

            echo "Remove documents\n";
            if (count($removeDocuments) > 0) {
                $this->index->deleteDocuments($removeDocuments);
            }

            echo "Done\n";
        }
    }

    /**
     * @return Document[]
     */
    private function indexDocuments(string $indexer): array
    {
        $documents = [];
        $indexedClasses = call_user_func(sprintf('%s::%s', $indexer, 'getExtendedClasses'));;

        foreach ($indexedClasses as $class) {
            echo "Indexing {$class}\n";

            /** @var IndexItemInterface $instance */
            $instance = $class::singleton();

            $mapping = $instance->getElasticaMapping();
            $mapping->setType($this->index->getType($class));

            echo "Create mapping\n";
            $mapping->send();
            echo "Done\n";

            echo "Create documents\n";

            /** @var IndexItemInterface $record */
            foreach (Versioned::get_by_stage($class, 'Live') as $record) {
                $documents[] = $record->getElasticaDocument();
                echo "Create documents\n";
            }
            echo "Done\n";
        }

        return $documents;
    }

    public function search(Query $query): ResultSet
    {
        return $this->index->search($query);
    }

    /**
     * @return string[]
     */
    private function getIndexClasses(): array
    {
        return [
            FilterIndexPageItemExtension::class,
            FilterIndexDataObjectItemExtension::class,
            GridElementIndexExtension::class,
        ];
    }

    public function getIndex(): Index
    {
        return $this->index;
    }

    protected function filterNotSearchable(&$documents)
    {
        $notSearchable = [];

        foreach ($documents as $index => $document) {
            if ($document->has('ShowInSearch') && !$document->get('ShowInSearch')) {
                $notSearchable[] = $document;
                unset($documents[$index]);
            }
        }

        return $notSearchable;
    }
}
