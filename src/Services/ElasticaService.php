<?php

namespace TheWebmen\Elastica\Services;

use Elastica\Document;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataObject;
use TheWebmen\Elastica\Extensions\FilterIndexDataObjectItemExtension;
use TheWebmen\Elastica\Extensions\FilterIndexItemExtension;
use TheWebmen\Elastica\Extensions\FilterIndexPageItemExtension;
use TheWebmen\Elastica\Traits\FilterIndexItemTrait;
use Translatable;

class ElasticaService
{
    use Extensible;
    use Injectable;
    use Configurable;

    private static $number_of_shards = 1;

    private static $number_of_replicas = 1;

    /**
     * @var \Elastica\Index
     */
    protected $index;

    public function __construct()
    {
        $config = self::config()->get('client_config');
        $client = new \Elastica\Client($config ? $config : []);
        $this->index = $client->getIndex(self::config()->get('index_name'));
    }

    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param FilterIndexItemTrait $record
     */
    public function add($record)
    {
        $type = $this->index->getType($record->getElasticaType());
        $type->addDocument($record->getElasticaDocument());
    }

    /**
     * @param FilterIndexItemTrait $record
     */
    public function delete($record)
    {
        $type = $this->index->getType($record->getElasticaType());
        $type->deleteDocument($record->getElasticaDocument());
    }

    public function reindex()
    {
        echo "Create index\n";
        $this->index->create([
            'number_of_shards' => self::config()->get('number_of_shards'),
            'number_of_replicas' => self::config()->get('number_of_replicas'),
        ], true);
        echo "Done\n";

        $documents = [];

        foreach ($this->getIndexedClasses() as $class) {
            echo "Indexing {$class}\n";

            /** @var FilterIndexItemTrait $instance */
            $instance = $class::singleton();
            $type = $this->index->getType($instance->getElasticaType());

            $mapping = $instance->getElasticaMapping();
            $mapping->setType($type);

            echo "Create mapping\n";
            $mapping->send();
            echo "Done\n";

            echo "Create documents\n";
            /** @var FilterIndexItemTrait $record */
            foreach ($class::get() as $record) {
                $documents[] = $record->getElasticaDocument();
                echo "Create documents\n";
            }
            echo "Done\n";
        }

        echo "Add documents\n";
        if (count($documents) > 0) {
            $this->index->addDocuments($documents);
        }
        echo "Done\n";
    }

    public function search(\Elastica\Query $query)
    {
        return $this->index->search($query);
    }

    public function getIndexedClasses()
    {
        $classes = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $candidate) {
            if (singleton($candidate)->hasExtension(FilterIndexPageItemExtension::class) ||
                singleton($candidate)->hasExtension(FilterIndexDataObjectItemExtension::class))
            {
                $classes[] = $candidate;
            }
        }
        return $classes;
    }
}
