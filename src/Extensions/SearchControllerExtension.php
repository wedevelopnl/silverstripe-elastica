<?php

namespace WeDevelop\Elastica\Extensions;

use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use WeDevelop\Elastica\Forms\FullTextSearchForm;
use WeDevelop\Elastica\Traits\Configurable;
use WeDevelop\Elastica\Traits\ElasticaConfigurable;

class SearchControllerExtension extends Extension
{
    use ElasticaConfigurable;
    use Extensible;

    /** @config   */
    private static array $allowed_actions = [
        'FullTextSearchForm',
    ];

    public function FullTextSearchForm(): FullTextSearchForm
    {
        $class = $this->getConfig('search_class');

        return new FullTextSearchForm($this->owner, $class);
    }

    /**
     * Process and render search results.
     *
     * @param array $data The raw request data submitted by user
     * @param FullTextSearchForm $form The form instance that was submitted
     * @param HTTPRequest $request Request generated for this action
     */
    public function results(array $data, FullTextSearchForm $form, HTTPRequest $request)
    {
        $data = [
            'Results' => $form->getResults(),
            'Query' => DBField::create_field('Text', $form->getSearchQuery()),
            'Title' => $this->getConfig('search_results_title') ?? _t('SilverStripe\\CMS\\Search\\SearchForm.SearchResults', 'Search Results')
        ];

        $this->extend('updateResultsData', $data);

        return $this->owner->customise($data)->renderWith(['WeDevelop\Elastica\Forms\Search_results', 'Page']);
    }
}
