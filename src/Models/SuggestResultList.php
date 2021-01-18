<?php

namespace TheWebmen\Elastica\Model;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;
use TheWebmen\Elastica\Services\ElasticaService;
use Elastica\ResultSet;
use SilverStripe\Control\HTTP;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class SuggestResultList extends ViewableData
{
    const RESULT_SIZE = 20;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var \Elastica\ResultSet
     */
    protected $resultSet;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var ElasticaService
     */
    private $elasticaService;

    public function __construct($indexName,string $field)
    {
        $this->elasticaService = Injector::inst()->get('ElasticaService')->setIndex($indexName);
        $this->field = $field;

        parent::__construct();
    }

    /**
     * @param string $url
     * @param int $pageNr
     * @param int $pageSize
     * @param int $pageOffset
     * @return array|\Elastica\ResultSet
     */
    public function getResultSet($query, array $options)
    {
        if (!$this->resultSet) {

            $this->setOptions($options);
            $this->resultSet = $this->getSuggestResult($this->elasticaService->suggest($this->field, $query, $this->options));
        }
        return $this->resultSet;
    }


    protected function setOptions(array $options)
    {

        if (!isset($options['size'])){
            $options['size'] = self::RESULT_SIZE;
        }
        if (isset($options['skip_duplicates'])){
            $options['skip_duplicates'] = (bool)$options['skip_duplicates'];
        } else{
            $options['skip_duplicates'] = true;
        }
        if (!isset($options['fuzzy'])){
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
     * @param ResultSet $searchResult
     * @param $currentPageNr
     * @return array
     */
    protected function getSuggestResult(ResultSet $searchResult)
    {
        $suggest = $searchResult->getSuggests();
        if (!isset($suggest[$this->field])) {
            return false;
        }
        $data = [];
        foreach ($suggest[$this->field][0]['options'] as $option) {
            $data[] = $option['text'];
        }
        return $data;
    }
}
