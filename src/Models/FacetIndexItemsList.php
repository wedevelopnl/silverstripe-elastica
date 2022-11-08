<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Model;

use Elastica\Index;
use Elastica\Query;
use Elastica\ResultSet;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Map;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;
use TheWebmen\Elastica\Services\ElasticaService;

final class FacetIndexItemsList extends ViewableData implements SS_List, Limitable
{
    private Index $index;

    private Query $query;

    private ?ResultSet $resultSet = null;

    public function __construct(Index $index, Query $query)
    {
        parent::__construct();

        $this->index = $index;
        $this->query = $query;
    }

    public function __clone()
    {
        $this->resultSet = null;
    }

    public function getResultSet(): ResultSet
    {
        if (!$this->resultSet) {
            $this->resultSet = ElasticaService::singleton()
                ->setIndex($this->index->getName())
                ->search($this->query);
        }

        return $this->resultSet;
    }

    public function limit($limit, $offset = 0): self
    {
        $this->query->setFrom($offset);
        $this->query->setSize($limit);

        return $this;
    }

    /**
     * @return SiteTree[]
     */
    public function toArray(): array
    {
        $rows = $this->getResultSet()->getResults();
        $pages = [];

        foreach ($rows as $row) {
            $pages[] = SiteTree::get()->byID($row->getData()['ID']);
        }

        return $pages;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function toNestedArray(): array
    {
        $result = [];

        foreach ($this as $item) {
            $result[] = $item->toMap();
        }

        return $result;
    }

    public function each($callback): FacetIndexItemsList
    {
        foreach ($this as $row) {
            $callback($row);
        }

        return $this;
    }

    public function map($keyField = 'ID', $titleField = 'Title'): Map
    {
        return new Map($this, $keyField, $titleField);
    }

    /**
     * @return \ArrayIterator<int, SiteTree>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    public function count(): int
    {
        return $this->getResultSet()->count();
    }

    public function getTotalItems(): int
    {
        $data = $this->getResultSet()->getResponse()->getData();

        return (int)($data['hits']['total']['value'] ?? 0);
    }

    public function first(): void
    {
    }

    public function last(): void
    {
    }

    public function find($key, $value): void
    {
    }

    /**
     * @return array<int, mixed>
     */
    public function column($colName = "ID"): array
    {
        return $this->toArrayList()->column($colName);
    }

    public function add($item): void
    {
    }

    public function remove($item): void
    {
    }

    public function offsetExists($offset): bool
    {
        return $this->toArrayList()->offsetExists($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->toArrayList()->offsetGet($offset);
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }

    private function toArrayList(): ArrayList
    {
        return ArrayList::create($this->toArray());
    }
}
