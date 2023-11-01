<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\ORM;

use Elastica\Client;
use Elastica\Query;
use Elastica\Result;
use Elastica\ResultSet;
use Elastica\Search;
use Iterator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

class SearchList extends ViewableData implements SS_List, Filterable, Sortable, Limitable
{
    private string $dataClass;

    private Query $query;

    private ?ResultSet $resultSet = null;

    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
        $this->query = new Query();

        parent::__construct();
    }

    public function __clone(): void
    {
        $this->query = clone $this->query;
        $this->resultSet = null;
    }

    public function toArray(): array
    {
        return array_map(function (Result $result) {
            return $this->createObject($result->getData());
        }, $this->getResultSet()->getResults());
    }

    public function toNestedArray(): array
    {
        return array_map(function (Result $result) {
            return (array)$this->createObject($result->getData());
        }, $this->getResultSet()->getResults());
    }

    public function count(): int
    {
        return $this->getResultSet()->count();
    }

    public function getIterator(): Iterator
    {
        foreach ($this->getResultSet()->getResults() as $result) {
            yield $this->createObject($result->getData());
        }
    }

    public function limit(?int $length, int $offset = 0): static
    {
        return $this->alterQuery(function (Query $query) use ($length, $offset) {
            $query->setSize($length);
            $query->setFrom($offset);
        });
    }

    public function alterQuery(callable $callback): static
    {
        $list = clone $this;
        $callback($list->query, $list);

        return $list;
    }

    public function getResultSet(): ResultSet
    {
        if (!$this->resultSet) {
            $search = new Search(Injector::inst()->get(Client::class));
            $search->addIndices(Config::inst()->get($this->dataClass, 'elastica_indices'));
            $search->setQuery($this->query);

            $this->resultSet = $search->search();
        }

        return $this->resultSet;
    }

    public function addMust(Query\AbstractQuery $must): static
    {
        return $this->alterQuery(function (Query $query) use ($must) {
            if (!$query->hasParam('query')) {
                $query->setQuery(new Query\BoolQuery());
            }

            $query->getQuery()->addMust($must);
        });
    }

    private function createObject(array $source): mixed
    {
        return Injector::inst()->createWithArgs($this->dataClass, [$source]);
    }

    public function offsetExists(mixed $offset): bool
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet(mixed $offset): mixed
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset(mixed $offset): void
    {
        // TODO: Implement offsetUnset() method.
    }

    public function canFilterBy($by)
    {
        // TODO: Implement canFilterBy() method.
    }

    public function filter()
    {
        // TODO: Implement filter() method.
    }

    public function filterAny()
    {
        // TODO: Implement filterAny() method.
    }

    public function exclude()
    {
        // TODO: Implement exclude() method.
    }

    public function filterByCallback($callback)
    {
        // TODO: Implement filterByCallback() method.
    }

    public function byID($id)
    {
        // TODO: Implement byID() method.
    }

    public function byIDs($ids)
    {
        // TODO: Implement byIDs() method.
    }

    public function add($item)
    {
        // TODO: Implement add() method.
    }

    public function remove($item)
    {
        // TODO: Implement remove() method.
    }

    public function first()
    {
        $array = $this->toArray();
        return reset($array);
    }

    public function last()
    {
        $array = $this->toArray();
        return reset($end);
    }

    public function map($keyfield = 'ID', $titlefield = 'Title')
    {
        // TODO: Implement map() method.
    }

    public function find($key, $value)
    {
        // TODO: Implement find() method.
    }

    public function column($colName = "ID")
    {
        // TODO: Implement column() method.
    }

    public function each($callback)
    {
        // TODO: Implement each() method.
    }

    public function canSortBy($by)
    {
        // TODO: Implement canSortBy() method.
    }

    public function sort()
    {
        // TODO: Implement sort() method.
    }

    public function reverse()
    {
        // TODO: Implement reverse() method.
    }
}
