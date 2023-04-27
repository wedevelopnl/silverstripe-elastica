<?php

namespace WeDevelop\Elastica\Models;

use Elastica\Query;
use Elastica\ResultSet;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;
use WeDevelop\Elastica\Extensions\SearchableObjectExtension;
use WeDevelop\Elastica\Services\ElasticaService;

class SearchResultList extends ViewableData
{
    private ElasticaService $elasticaService;

    private array $resultSet;

    public function __construct(SearchableObjectExtension $extensionInstance, protected Query $query)
    {
        $this->elasticaService = Injector::inst()->get('ElasticaService')->setIndex($extensionInstance->getStringifiedIndexName());

        parent::__construct();
    }

    public function getResultSet(string $url, string $pageVar, int $pageSize, int $pageOffset, int $pageNr = 1, $moveOffset = true)
    {
        if (!isset($this->resultSet)) {
            $this->resultSet = $this->getSearchResult($this->elasticaService->search($this->query), $url, $pageVar, $pageSize, $pageOffset, $pageNr, $moveOffset);
        }

        return $this->resultSet;
    }

    /**
     * @param ResultSet $searchResult
     * @param $currentPageNr
     * @return array
     */
    protected function getSearchResult(
        ResultSet $searchResult,
        string $currentUrl,
        string $pageVar,
        int $pageSize,
        int $pageOffset,
        int $currentPageNr = 1,
        bool $moveOffset = true)
    {
        $dataList = new \SilverStripe\ORM\ArrayList();
        $rows = $searchResult->getResults();

        foreach ($rows as $row) {
            $data = $row->getData();
            $dataList->add($data);
        }

        $resultCount = $searchResult->getTotalHits();

        $pageCount = ceil($resultCount / $pageSize);

        if ($currentPageNr > 1) {
            $prevPageNr = $currentPageNr - 1;
            $prevLink = Http::setGetVar($pageVar, $prevPageNr, $currentUrl);
        }

        if ($currentPageNr < $pageCount) {
            $nextLinkPageNr = $currentPageNr + 1;
            $nextLink = Http::setGetVar($pageVar, $nextLinkPageNr, $currentUrl);
        }

        $data = [
            'Data' => $dataList,
            'MoreThanOnePage' => $pageCount > 1,
            'NotFirstPage' => $currentPageNr <> 1,
            'NotLastPage' => $currentPageNr <> $pageCount,
            'PrevLink' => isset($prevLink) ? $prevLink : null,
            'NextLink' => isset($nextLink) ? $nextLink : null,
            'PageLinks' => $this->paginationLinks($pageVar, $currentPageNr, $pageCount, $currentUrl, $pageOffset, $moveOffset),
            'CountResults' => $resultCount
        ];

        return $data;
    }

    protected function paginationLinks(string $pageVar, int $current, int $total, string $url, int $offset, $moveOffset = true)
    {
        $result = new ArrayList();

        $left = max($current - $offset, 1);
        $right = min($current + $offset, $total);

        $offsetTotal = $offset * 2;

        // should total offest be set on one side for the first or last pages
        if ($moveOffset) {
            if ($left + $offsetTotal > $total) {
                $left = $total - $offsetTotal;
            }

            if ($right < $offsetTotal +1 ){
                $right = $offsetTotal +1;
            }
        }

        $range = range($left, $right);

        for ($i = 0; $i < $total; $i++) {

            $num = $i + 1;

            $emptyRange = $num != 1 && $num != $total && (
                    $num == $left - 1 || $num == $right + 1
                );

            $link = Http::setGetVar($pageVar, $num, $url);

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
