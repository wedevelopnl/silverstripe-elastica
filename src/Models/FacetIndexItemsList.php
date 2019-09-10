<?php

namespace TheWebmen\Elastica\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Map;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;
use TheWebmen\Elastica\Services\ElasticaService;

class FacetIndexItemsList extends ViewableData implements SS_List, Limitable
{

    /**
     * @var \Elastica\Index
     */
    protected $index;

    /**
     * @var \Elastica\Query
     */
    protected $query;

    /**
     * @var \Elastica\ResultSet
     */
    protected $resultSet;

    public function __construct(\Elastica\Index $index, \Elastica\Query $query)
    {
        $this->index = $index;
        $this->query = $query;

        parent::__construct();
    }

    public function __clone()
    {
        $this->resultSet = null;
    }

    public function getResultSet()
    {
        if (!$this->resultSet) {
            $this->resultSet = ElasticaService::singleton()->search($this->query);
        }
        return $this->resultSet;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return FacetIndexItemsList
     */
    public function limit($limit, $offset = 0)
    {
        $this->query->setFrom($offset);
        $this->query->setSize($limit);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $rows = $this->getResultSet()->getResults();
        $pages = [];

        foreach ($rows as $row) {
            $pages[] = SiteTree::get()->byID($row->getData()['ID']);
        }

        return $pages;
    }

    /**
     * @return array
     */
    public function toNestedArray()
    {
        $result = [];

        foreach ($this as $item) {
            $result[] = $item->toMap();
        }

        return $result;
    }

    /**
     * @param callable $callback
     * @return FacetIndexItemsList
     */
    public function each($callback)
    {
        foreach ($this as $row) {
            $callback($row);
        }

        return $this;
    }

    /**
     * @param string $keyField - the 'key' field of the result array
     * @param string $titleField - the value field of the result array
     * @return Map
     */
    public function map($keyField = 'ID', $titleField = 'Title')
    {
        return new Map($this, $keyField, $titleField);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->getResultSet()->count();
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        $data = $this->getResultSet()->getResponse()->getData();

        return (int) ($data['hits']['total']['value'] ?? 0);
    }

    public function first()
    {
    }

    public function last()
    {
    }

    public function find($key, $value)
    {
    }

    public function column($colName = "ID")
    {
    }

    public function add($item)
    {
    }

    public function remove($item)
    {
    }

    public function offsetExists($key)
    {
    }

    public function offsetGet($key)
    {
    }

    public function offsetSet($key, $value)
    {
        user_error("Can't alter items in a DataList using array-access", E_USER_ERROR);
    }

    public function offsetUnset($key)
    {
        user_error("Can't alter items in a DataList using array-access", E_USER_ERROR);
    }
}
