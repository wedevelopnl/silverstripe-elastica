<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Extension;

use Elastica\Query;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\ViewableData;
use WeDevelop\Elastica\Form\FilterForm;
use WeDevelop\Elastica\ORM\SearchList;

class FilterPageControllerExtension extends Extension
{
    /** @config */
    private static int $items_per_page = 12;

    public function index(): ViewableData
    {
        $page = $this->getOwner()->data();
        $filters = $page->Filters()->filter('Enabled', true)->toArray();
        $sorts = $page->SortOptions()->map('URLSegment', 'Title')->toArray();
        $form = FilterForm::create($this->owner, 'FilterForm', $sorts, $filters);

        if (method_exists($this->getOwner(), 'updateFilterForm')) {
            $this->getOwner()->updateFilterForm($form);
        }

        $list = SearchList::create($this->getOwner()->data()->getReadable());

        foreach ($filters as $filter) {
            $list = $filter->alterList($list, $filters);
        }

        $sortValue = $form->Fields()->fieldByName('Sort')?->Value() ?? array_key_first($sorts);
        $sortOption = $sortValue ? $page->SortOptions()->find('URLSegment', $sortValue) : null;
        if ($sortOption) {
            $list = $list->alterQuery(function (Query $query) use ($sortOption) {
                $query->setSort($sortOption->toArray());
            });
        }

        $resultSet = $list->getResultSet();

        foreach ($filters as $filter) {
            $filter->applyContext($resultSet);
        }

        if (method_exists($this->getOwner(), 'updateList')) {
            $this->getOwner()->updateList($list);
        }

        $paginatedList = PaginatedList::create($list, $this->getOwner()->getRequest())
            ->setPageLength($this->getOwner()->config()->get('items_per_page'));

        if (method_exists($this->getOwner(), 'updatePaginatedList')) {
            $this->getOwner()->updatePaginatedList($paginatedList);
        }

        return $this->getOwner()->customise([
            'FilterForm' => $form,
            'FilteredList' => $paginatedList,
        ]);
    }
}
