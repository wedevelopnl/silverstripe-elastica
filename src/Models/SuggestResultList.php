<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Model;

use SilverStripe\View\ViewableData;
use TheWebmen\Elastica\Services\ElasticaService;
use Elastica\ResultSet;

class SuggestResultList extends ViewableData
{
    private const RESULT_SIZE = 20;

    private string $field;

    /**
     * @return string[]
     */
    private array $resultSet;

    private array $options;

    private ElasticaService $elasticaService;

    public function __construct(string $indexName, string $field)
    {
        parent::__construct();

        $this->elasticaService = ElasticaService::singleton();
        $this->elasticaService->setIndex($indexName);
        $this->field = $field;
    }

    /**
     * @param array<string, mixed> $options
     * @return string[]
     */
    public function getResultSet(string $query, array $options): array
    {
        if (!$this->resultSet) {
            $this->setOptions($options);
            $this->resultSet = $this->getSuggestResult($this->elasticaService->suggest($this->field, $query, $this->options));
        }
        return $this->resultSet;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function setOptions(array $options): void
    {
        if (!isset($options['size'])) {
            $options['size'] = self::RESULT_SIZE;
        }
        if (isset($options['skip_duplicates'])) {
            $options['skip_duplicates'] = (bool)$options['skip_duplicates'];
        } else {
            $options['skip_duplicates'] = true;
        }
        if (!isset($options['fuzzy'])) {
            $options['fuzzy'] = false;
        } else {
            if (!isset($options['fuzzy']['fuzziness'])) {
                $options['fuzzy']['fuzziness'] = "AUTO:3,6";
            }
            if (!isset($options['fuzzy']['min_length'])) {
                $options['fuzzy']['min_length'] = 3;
            }
            if (!isset($options['fuzzy']['prefix_length'])) {
                $options['fuzzy']['prefix_length'] = 1;
            }
        }
        $this->options = $options;
    }

    /**
     * @return string[]
     */
    private function getSuggestResult(ResultSet $searchResult): array
    {
        $suggest = $searchResult->getSuggests();

        return array_map(function (array $option) {
            return $option['text'];
        }, $suggest[$this->field][0]['options']);
    }
}
