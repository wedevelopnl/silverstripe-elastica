<?php

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\PaginatedList;
use TheWebmen\Elastica\Filters\Filter;
use TheWebmen\Elastica\Forms\FilterForm;
use TheWebmen\Elastica\Model\FacetIndexItemsList;
use TheWebmen\Elastica\Services\ElasticaService;

/**
 * @method FilterPageExtension data
 * @property RequestHandler|FilterPageControllerExtension owner
 */
class FilterPageControllerExtension extends Extension
{
    private static $items_per_page = 24;

    private static $allowed_actions = [
        'FilterForm'
    ];

    private static $sorts = [
        'Relevance' => [
            'FieldName' => 'ID',
            'Direction' => 'asc',
        ],
        'Title ascending' => [
            'FieldName' => 'Title',
            'Direction' => 'asc',
        ]
    ];

    /**
     * @var FacetIndexItemsList
     */
    private $list;

    /**
     * @var Filter[]
     */
    private $filters;

    public function getFilters()
    {
        if (!$this->filters) {
            $this->filters = $this->owner->data()->Filters()->toArray();
        }

        return $this->filters;
    }

    public function FilterForm()
    {
        $form = new FilterForm($this->owner, 'FilterForm');

        if (method_exists($this->owner, 'updateFilterForm')) {
            $this->owner->updateFilterForm($form);
        }

        return $form;
    }

    public function PaginatedFilterList()
    {
        $list = new PaginatedList($this->getFilterList());
        $list->setPageLength($this->owner->config()->get('items_per_page'));
        $list->setRequest($this->owner->getRequest());

        if (method_exists($this->owner, 'updatePaginatedFilterList')) {
            $this->owner->updatePaginatedFilterList($list);
        }

        return $list;
    }

    public function getFilterList()
    {
        if (!$this->list) {
            $query = new \Elastica\Query();
            $bool = new \Elastica\Query\BoolQuery();

            foreach ($this->getFilters() as $filter) {
                $filterQuery = $filter->getElasticaQuery();

                if ($filterQuery) {
                    $bool->addMust($filterQuery);
                }

                $aggregation = $filter->getAggregation($this->getFilters());
                if ($aggregation) {
                    $query->addAggregation($aggregation);
                }
            }

            $query->setQuery($bool);

            $sorts = $this->owner->config()->get('sorts');
            if ($sorts) {
                $sortValue = $this->owner->getRequest()->getVar('sort');
                if (array_key_exists($sortValue, $sorts)) {
                    $sort = $sorts[$sortValue];
                } else {
                    $sort = reset($sorts);
                }

                $query->setSort([$sort['FieldName'] => $sort['Direction']]);
            }

            if (method_exists($this->owner, 'updateQuery')) {
                $this->owner->updateQuery($query);
            }

            $list = new FacetIndexItemsList(ElasticaService::singleton()->getIndex(), $query);

            $this->list = $list;
        }

        return $this->list;
    }
}
