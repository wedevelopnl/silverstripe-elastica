<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Services;

use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Suggest;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use WeDevelop\Elastica\Extensions\PageExtension;
use WeDevelop\Elastica\Extensions\SearchableObjectExtension;
use WeDevelop\Elastica\Extensions\ShowInSearchAwareOfExtension;


final class ElasticaService
{
    use Extensible;
    use Injectable;
    use Configurable;

    public const SUGGEST_FIELD_NAME = 'suggest';

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
    }

    public function setIndex(string $indexName): self
    {
        $this->extend('updateIndexName', $indexName);

        $this->index = $this->client->getIndex($indexName);

        return $this;
    }

    public function add(SearchableObjectExtension $record): void
    {
        $this->index->addDocument($record->getElasticaDocument());
    }

    public function delete(SearchableObjectExtension $record): void
    {
        $this->index->deleteById($record->getElasticaId());
    }

    public function reindex(): void
    {
        $indexableClasses = SearchableObjectExtension::getExtendedClasses(false);

        $settings = [
            'number_of_shards' => self::config()->get('number_of_shards'),
            'number_of_replicas' => self::config()->get('number_of_replicas'),
        ];

        foreach ($indexableClasses as $class) {
            $indexer = SearchableObjectExtension::createInstance($class);

            $this->setIndex($indexer->getIndexNames()[0]);

            echo "Create index {$this->getIndex()->getName()} \n";

            $this->index->create(['settings' => array_merge($settings, $indexer->getElasticaSettings())], true);
            echo "Done\n";

            $documents = $this->indexDocuments($indexer, $class);

            $this->extend('updateReindexDocuments', $documents);

            echo "Add documents\n";
            if (count($documents) > 0) {
                $this->index->addDocuments($documents);
            }
        }
    }

    /**
     * @return Document[]
     */
    private function indexDocuments(SearchableObjectExtension $indexer, string $class): array
    {
        $documents = [];

        echo "Indexing {$class}\n";

        $mapping = $indexer->getElasticaMapping();

        echo "Create mapping\n";
        $mapping->send($this->index);
        echo "Done\n";

        echo "Create documents\n";

        $records = $class::get();

        foreach ($records as $record) {
            /** @var  SearchableObjectExtension $searchableInstance */
            $searchableInstance = $record->getExtensionInstance(SearchableObjectExtension::class);
            $searchableInstance->setOwner($record);
            $documents[] = $searchableInstance->getElasticaDocument();
            echo "Create documents\n";
        }

        echo "Done\n";

        return $documents;
    }

    public function search(Query $query): ResultSet
    {
        return $this->index->search($query);
    }

    public function getIndex(): Index
    {
        return $this->index;
    }
}
