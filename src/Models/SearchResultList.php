<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Model;

use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ViewableData;
use TheWebmen\Elastica\Services\ElasticaService;
use Elastica\ResultSet;
use SilverStripe\Control\HTTP;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

final class SearchResultList extends ViewableData
{
    private const RESULT_COUNT_VAR = 'resultCount';
    private const PAGE_VAR = 'page';

    private \Elastica\Query $query;

    /**
     * @return array<string, mixed>
     */
    private array $resultSet;

    private ElasticaService $elasticaService;

    public function __construct(string $indexName, \Elastica\Query $query)
    {
        parent::__construct();

        $this->elasticaService = ElasticaService::singleton();
        $this->elasticaService->setIndex($indexName);
        $this->query = $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResultSet(
        string $url,
        int $pageNr,
        int $pageSize,
        int $pageOffset,
        bool $moveOffset = true
    ): array {
        if (!$this->resultSet) {
            $this->resultSet = $this->getSearchResult(
                $this->elasticaService->search($this->query),
                $url,
                $pageNr,
                $pageSize,
                $pageOffset,
                $moveOffset,
            );
        }

        return $this->resultSet;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSearchResult(
        ResultSet $searchResult,
        string $currentUrl,
        int $currentPageNr,
        int $pageSize,
        int $pageOffset,
        bool $moveOffset = true
    ): array {
        $dataList = ArrayList::create();
        $rows = $searchResult->getResults();

        foreach ($rows as $row) {
            $data = $row->getData();
            $content = DBHTMLText::create()->setValue($data['Content']);
            $data['Content'] = $content;
            $dataList->add($data);
        }

        $resultCount = $searchResult->getAggregation(self::RESULT_COUNT_VAR)['value'];

        $pageCount = (int)ceil($resultCount / $pageSize);

        if ($currentPageNr > 1) {
            $prevPageNr = $currentPageNr - 1;
            $prevLink = Http::setGetVar(self::PAGE_VAR, (string)$prevPageNr, $currentUrl);
        }

        if ($currentPageNr < $pageCount) {
            $nextLinkPageNr = $currentPageNr + 1;
            $nextLink = Http::setGetVar(self::PAGE_VAR, (string)$nextLinkPageNr, $currentUrl);
        }

        return [
            'Data' => $dataList,
            'MoreThanOnePage' => $pageCount > 1,
            'NotFirstPage' => $currentPageNr !== 1,
            'NotLastPage' => $currentPageNr !== $pageCount,
            'PrevLink' => $prevLink ?? null,
            'NextLink' => $nextLink ?? null,
            'PageLinks' => $this->paginationLinks($currentPageNr, $pageCount, $currentUrl, $pageOffset, $moveOffset),
            'CountResults' => $resultCount,
        ];
    }

    private function paginationLinks(
        int $current,
        int $total,
        string $url,
        int $offset,
        bool $moveOffset = true
    ): ArrayList {
        $result = ArrayList::create();

        $left = max($current - $offset, 1);
        $right = min($current + $offset, $total);

        $offsetTotal = $offset * 2;

        // should total offest be set on one side for the first or last pages
        if ($moveOffset) {
            if ($left + $offsetTotal > $total) {
                $left = $total - $offsetTotal;
            }

            if ($right < $offsetTotal +1) {
                $right = $offsetTotal +1;
            }
        }

        $range = range($left, $right);

        for ($i = 0; $i < $total; $i++) {
            $num = $i + 1;

            $emptyRange = $num !== 1 && $num !== $total && (
                $num === $left - 1 || $num === $right + 1
            );

            $link = Http::setGetVar(self::PAGE_VAR, (string)$num, $url);

            if ($emptyRange) {
                $result->push(ArrayData::create([
                    'PageNum' => null,
                    'Link' => null,
                    'CurrentBool' => false,
                ]));
            } elseif ($num === 1 || $num === $total || in_array($num, $range, true)) {
                $result->push(ArrayData::create([
                    'PageNum' => $num,
                    'Link' => $link,
                    'CurrentBool' => $current === $num,
                ]));
            }
        }

        return $result;
    }
}
