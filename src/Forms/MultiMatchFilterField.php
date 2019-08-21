<?php

namespace TheWebmen\Elastica\Forms;

use Elastica\Suggest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\TextField;
use TheWebmen\Elastica\Filters\MultiMatchFilter;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

/**
 * @method FilterForm getForm
 */
class MultiMatchFilterField extends TextField implements FilterFieldInterface
{
    use FilterFieldTrait;

    private static $allowed_actions = [
        'autocomplete'
    ];

    public function FieldHolder($properties = array())
    {
        $this->setAttribute('data-autocomplete', $this->Link('autocomplete'));

        return parent::FieldHolder($properties);
    }

    public function autocomplete()
    {
        $query = $this->getAutocompleteQuery();
        $response = ElasticaService::singleton()->search($query);
        $results = [];

        foreach ($response->getSuggests()['Completion'][0]['options'] as $document) {
            $results[] = $document['_source'][$this->getFilter()->AutocompleteTitleFieldName];
        }

        $response = new HTTPResponse();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode($results));

        return $response;
    }

    protected function getAutocompleteQuery()
    {
        $q = $this->getForm()->getController()->getRequest()->getVar('q');

        $query = new \Elastica\Query();

        $completion = new \Elastica\Suggest\Completion('Completion', $this->getFilter()->AutocompleteFieldName);
        $completion->setPrefix($q);

        $suggest = new \Elastica\Suggest();
        $suggest->addSuggestion($completion);

        $query->setSuggest($suggest);

        return $query;
    }
}
