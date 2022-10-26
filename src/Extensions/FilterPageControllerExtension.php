<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Extensions;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Extension;
use TheWebmen\Elastica\Filters\Filter;
use TheWebmen\Elastica\Forms\FilterForm;
use TheWebmen\Elastica\Interfaces\AggregatableFilterInterface;
use TheWebmen\Elastica\Model\FacetIndexItemsList;
use TheWebmen\Elastica\Model\PaginatedList;
use TheWebmen\Elastica\Services\ElasticaService;

/**
 * @property FilterPageControllerExtension $owner
 * @method FilterPageExtension data()
 * @mixin ContentController
 */
final class FilterPageControllerExtension extends Extension
{
    /** @config */
    private static int $items_per_page = 24;

    /** @config */
    private static array $allowed_actions = [
        'FilterForm',
    ];

    private ?FacetIndexItemsList $list = null;

    private array $filters = [];

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        if (!$this->filters) {
            $this->filters = $this->owner->data()->Filters()->toArray();
        }

        return $this->filters;
    }

    public function FilterForm(): FilterForm
    {
        $form = FilterForm::create($this->owner, 'FilterForm');

        if (method_exists($this->owner, 'updateFilterForm')) {
            $this->owner->updateFilterForm($form);
        }

        return $form;
    }

    public function PaginatedFilterList(): PaginatedList
    {
        $list = PaginatedList::create($this->getFilterList());
        $list->setPageLength($this->owner->config()->get('items_per_page'));
        $list->setRequest($this->owner->getRequest());

        if (method_exists($this->owner, 'updatePaginatedFilterList')) {
            $this->owner->updatePaginatedFilterList($list);
        }

        return $list;
    }

    public function getFilterList(): FacetIndexItemsList
    {
        if (!$this->list) {
            $query = new \Elastica\Query();
            $bool = new \Elastica\Query\BoolQuery();

            foreach ($this->getFilters() as $filter) {
                $filterQuery = $filter->getElasticaQuery();

                if ($filterQuery) {
                    $bool->addMust($filterQuery);
                }

                if ($filter instanceof AggregatableFilterInterface) {
                    $aggregation = $filter->getAggregation($this->getFilters());
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

            $elasticaService = ElasticaService::singleton()->setIndex(FilterIndexPageItemExtension::getIndexName());

            $this->list = FacetIndexItemsList::create($elasticaService->getIndex(), $query);
        }

        return $this->list;
    }
}
