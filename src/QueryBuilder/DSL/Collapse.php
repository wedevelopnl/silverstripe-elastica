<?php
namespace TheWebmen\Elastica\QueryBuilder\DSL;

use Elastica\Aggregation\AbstractSimpleAggregation;
use Elastica\Param;
use  Elastica\QueryBuilder\DSL;

class Collapse extends AbstractCollapse implements DSL
{

    /**
     * must return type for QueryBuilder usage.
     *
     * @return string
     */
    public function getType()
    {
        return 'collapse';
    }

    /**
     * Set the field for this collapse.
     *
     * @param string $field the name of the document field on which to perform this collapse
     *
     * @return $this
     */
    public function setField($field)
    {
        return $this->setParam('field', $field);
    }
}
