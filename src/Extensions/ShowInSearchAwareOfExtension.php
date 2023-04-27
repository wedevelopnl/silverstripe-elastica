<?php

namespace WeDevelop\Elastica\Extensions;

use Elastica\Query\BoolQuery;
use Elastica\Query\MatchQuery;
use SilverStripe\Core\Extension;

class ShowInSearchAwareOfExtension extends Extension
{
    /** @config */
    private static array $db = [
        'ShowInSearch' => 'Boolean(true)',
    ];

    public function updateElasticaFields(&$fields): void
    {
        $fields['ShowInSearch'] = ['type' => 'boolean'];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateElasticaDocumentData(array &$data): void
    {
        $data['ShowInSearch'] = (bool)$this->getOwner()->ShowInSearch;
    }

    public function updateFullTextBoolQuery(BoolQuery &$bool): void
    {
        $showInSearchQuery = new MatchQuery('ShowInSearch', true);
        $bool->addMust($showInSearchQuery);
    }

    /**
     * extend hook for BaseElemet
     */
    public function updateContentForSearchIndex(&$content): void
    {
        if (!$this->owner->ShowInSearch) {
            $content = '';
        }
    }
}
