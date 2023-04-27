<?php

namespace WeDevelop\Elastica\Forms;

use SilverStripe\Forms\TextField;
use WeDevelop\Elastica\Extensions\SearchableObjectExtension;
use WeDevelop\Elastica\Models\SearchResultList;

class FullTextSearchForm extends \SilverStripe\CMS\Search\SearchForm
{
    protected string $searchFieldName = 'Search';
    protected string $searchableClass;

    public function __construct(
        \SilverStripe\Control\RequestHandler $controller = null,
        string $searchableClass,
        $name = 'FullTextSearchForm',
        \SilverStripe\Forms\FieldList $fields = null,
        \SilverStripe\Forms\FieldList $actions = null
    ) {
        $field = TextField::create($this->searchFieldName)->setAttribute('placeholder', 'Search');
        $this->searchableClass = $searchableClass;

        parent::__construct($controller, $name, $fields, $actions);
    }

    public function getResults(): array
    {
        $request = $this->getRequestHandler()->getRequest();

        $search = $request->getVar($this->searchFieldName);

        /** @var SearchableObjectExtension $searchableInstance */
        $searchableInstance = SearchableObjectExtension::createInstance($this->searchableClass);

        $query = $searchableInstance->buildFullTextSaerchQuery($search);

        $currentUrl = $request->getURL(true);

        $pageSize = $searchableInstance->getPageSize();
        $pageVar = $searchableInstance->getPageVar();
        $pageNr = $request->getVar($pageVar);

        $query->setSize($pageSize);
        if ($pageNr) {
            $query->setFrom($pageSize * ($pageNr -1));
        }

        $searchResultList = new SearchResultList($searchableInstance, $query);
        return $searchResultList->getResultSet(
            $currentUrl,
            $pageVar,
            $searchableInstance->getPageSize(),
            $searchableInstance->getPageLinkShowOffsetSize(),
            $pageNr ? $pageNr : 1
        );
    }
}
