<?php

namespace WeDevelop\Elastica\Extensions;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\HasManyList;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Model\PaginatedList;
use WeDevelop\Elastica\Filters\Filter;
use WeDevelop\Elastica\Forms\FilterForm;
use WeDevelop\Elastica\Model\FacetIndexItemsList;
use WeDevelop\Elastica\Traits\Configurable;
use WeDevelop\Elastica\Traits\ElasticaConfigurable;

class FilterPageControllerExtension extends Extension
{
    use Extensible;
    use ElasticaConfigurable;

    /** @config */
    private static array $allowed_actions = [
        'FilterForm',
    ];

    private FacetIndexItemsList $list;

    public function __construct(protected array $filters = [])
    {
    }

    public function FilterForm(): FilterForm
    {
        $form = FilterForm::create($this->owner, 'FilterForm');

        if (method_exists($this->owner, 'updateFilterForm')) {
            $this->owner->updateFilterForm($form);
        }

        return $form;
    }

    public function getFilters(): array
    {
        if (!$this->filters) {
            $this->filters = $this->owner->data()->Filters()->toArray();
            if ($this->getOwner()->data()->hasExtension(FilterPageExtension::class)) {
                $configFilters = $this->getOwner()->data()->getFiltersFromConfig();(
                $this->filters = array_merge($this->filters, $configFilters));
            }
        }

        return $this->filters;
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

    public function getFilterList()
    {
        if (!isset($this->list)) {
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

            $elasticaService = Injector::inst()->get('ElasticaService')->setIndex($this->getSearchIndex());

            $list = new FacetIndexItemsList($elasticaService->getIndex(), $query);

            $this->list = $list;
        }

        return $this->list;
    }

    private function getSearchIndex(): ?string
    {
        $filterPage = $this->getOwner()->data();
        if (!$filterPage->hasExtension(FilterPageExtension::class)) {
            return null;
        }

        $searchClass = $filterPage->getElasticaConfig('filter_class');
        $searchClassSearchableInstance = SearchableObjectExtension::createInstance($searchClass);

        return $searchClassSearchableInstance->getStringifiedIndexName();
    }
}
