<?php
namespace TheWebmen\Elastica\QueryBuilder;

use TheWebmen\Elastica\QueryBuilder\DSL\Collapse;

class Query extends \Elastica\Query
{


    /**
     * Adds an Aggregation to the query.
     *
     * @param AbstractAggregation $agg
     *
     * @return $this
     */
    public function addCollapse(Collapse $collapse)
    {
        $this->_params['collapse'] = $collapse;

        return $this;
    }

    /**
     * Converts all query params to an array.
     *
     * @return array Query array
     */
    public function toArray()
    {

        $array = parent::toArray();

        if (isset($array['collapse'])) {
            $array['collapse'] = $array['collapse']['collapse'];
        }

        return $array;
    }
}
