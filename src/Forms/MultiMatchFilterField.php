<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use TheWebmen\Elastica\Interfaces\FilterFieldInterface;
use TheWebmen\Elastica\Services\ElasticaService;
use TheWebmen\Elastica\Traits\FilterFieldTrait;

final class MultiMatchFilterField extends TextField implements FilterFieldInterface
{
    use FilterFieldTrait;

    /** @config */
    private static array $allowed_actions = [
        'autocomplete',
    ];

    /**
     * @param array<string, mixed> $properties
     */
    public function FieldHolder($properties = []): DBHTMLText
    {
        $this->setAttribute('data-autocomplete', $this->Link('autocomplete'));

        return parent::FieldHolder($properties);
    }

    public function autocomplete(): HTTPResponse
    {
        $query = $this->getAutocompleteQuery();

        $elasticaService = ElasticaService::singleton();
        $response = $elasticaService->search($query);
        $results = [];

        foreach ($response->getSuggests()['Completion'][0]['options'] as $document) {
            $results[] = $document['_source'][$this->getFilter()->AutocompleteTitleFieldName];
        }

        return HTTPResponse::create()
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode($results, JSON_THROW_ON_ERROR));
    }

    private function getAutocompleteQuery(): \Elastica\Query
    {
        $q = $this->getForm()->getController()->getRequest()->requestVar('q');

        if (!$q) {
            throw new \Exception('Query param not found');
        }

        $query = new \Elastica\Query();

        $completion = new \Elastica\Suggest\Completion('Completion', $this->getFilter()->AutocompleteFieldName);
        $completion->setPrefix($q);

        $suggest = new \Elastica\Suggest();
        $suggest->addSuggestion($completion);

        $query->setSuggest($suggest);

        return $query;
    }
}
