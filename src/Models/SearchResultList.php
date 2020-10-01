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

class SearchResultList extends ViewableData
{
    const RESULT_COUNT_VAR = 'resultCount';
    const PAGE_SIZE = 10;
    const PAGE_VAR = 'page';

    /**
     * @var \Elastica\Query
     */
    protected $query;

    /**
     * @var \Elastica\ResultSet
     */
    protected $resultSet;

    /**
     * @var ElasticaService
     */
    private $elasticaService;

    public function __construct($indexName, \Elastica\Query $query)
    {
        $this->elasticaService = Injector::inst()->get('ElasticaService')->setIndex($indexName);
        $this->query = $query;

        parent::__construct();
    }

    /**
     * @param strign $url
     * @param int $pageNr
     * @param int $pageOffset
     * @return array|\Elastica\ResultSet
     */
    public function getResultSet(string $url, int $pageNr, int $pageOffset)
    {
        if (!$this->resultSet) {

            $this->resultSet = $this->getSearchResult($this->elasticaService->search($this->query), $url, $pageNr, $pageOffset);
        }
        return $this->resultSet;
    }

    /**
     * //     *
     * //     * @param ResultSet $searchResult
     * //     * @param $currentPageNr
     * //     * @return array
     * //     */
    protected function getSearchResult(ResultSet $searchResult, $currentUrl, $currentPageNr = 1, $pageOffset)
    {

        $dataList = new \SilverStripe\ORM\ArrayList();
        $rows = $searchResult->getResults();

        foreach ($rows as $row) {
            $data = $row->getData();
            $content = \SilverStripe\ORM\FieldType\DBHTMLText::create()->setValue($data['Content']);
            $data['Content'] = $content;
            $dataList->add($data);
        }

        $resultCount = $searchResult->getAggregation(self::RESULT_COUNT_VAR)['value'];

        $pageCount = ceil($resultCount / self::PAGE_SIZE);


        if ($currentPageNr > 1) {
            $prevPageNr = $currentPageNr - 1;
            $prevLink = Http::setGetVar(self::PAGE_VAR, $prevPageNr, $currentUrl);
        }

        if ($currentPageNr < $pageCount) {
            $nextLinkPageNr = $currentPageNr + 1;
            $nextLink = Http::setGetVar(self::PAGE_VAR, $nextLinkPageNr, $currentUrl);
        }

        $data = [
            'Data' => $dataList,
            'MoreThanOnePage' => $pageCount > 1,
            'NotFirstPage' => $currentPageNr <> 1,
            'NotLastPage' => $currentPageNr <> $pageCount,
            'PrevLink' => isset($prevLink) ? $prevLink : null,
            'NextLink' => isset($nextLink) ? $nextLink : null,
            'PageLinks' => $this->paginationLinks($currentPageNr, $pageCount, $currentUrl, $pageOffset)
        ];

        return $data;
    }

    /**
     * @param $current
     * @param $total
     * @param $url
     * @param int $offset
     * @return ArrayList
     */
    protected function paginationLinks($current, $total, $url, $offset)
    {
        $result = new ArrayList();

        $left = max($current - $offset, 1);
        $right = min($current + $offset, $total);
        $range = range($current - $offset, $current + $offset);

        $offsetTotal = $offset * 2;

        if ($left + $offsetTotal > $total) {
            $left = $total - $offsetTotal;
        }

        for ($i = 0; $i < $total; $i++) {

            $num = $i + 1;

            $emptyRange = $num != 1 && $num != $total && (
                    $num == $left - 1 || $num == $right + 1
                );

            $link = Http::setGetVar(self::PAGE_VAR, $num, $url);

            if ($emptyRange) {
                $result->push(new ArrayData(array(
                    'PageNum' => null,
                    'Link' => null,
                    'CurrentBool' => false
                )));
            } elseif ($num == 1 || $num == $total || in_array($num, $range)) {
                $result->push(new ArrayData(array(
                    'PageNum' => $num,
                    'Link' => $link,
                    'CurrentBool' => $current == $num
                )));
            }
        }

        return $result;
    }
}
