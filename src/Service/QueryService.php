<?php

namespace Subugoe\FindBundle\Service;

use Solarium\Component\FacetSet;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;

/**
 * Services for query manipulation and extraction.
 */
class QueryService
{
    private $defaultQuery;

    private $defaultSort;

    private $hidden;

    private $facets;

    public function __construct($defaultQuery, $defaultSort, $hidden, $facets)
    {
        $this->defaultQuery = $defaultQuery;
        $this->defaultSort = $defaultSort;
        $this->hidden = $hidden;
        $this->facets = $facets;
    }

    /**
     * Returns the sorting part of the query.
     *
     * @return array $sort
     */
    public function getSorting(string $sortString = ''): array
    {
        if (empty($sortString)) {
            $sortString = $this->defaultSort;
        }
        $sort = [];
        if (preg_match('/\s/', $sortString)) {
            $sort = explode(' ', $sortString);
        }

        return $sort;
    }

    public function composeQuery(string $query): string
    {
        $queryComposer = [];
        $queryComposer[] = $this->defaultQuery;
        if (!empty($query)) {
            $queryComposer[] = $query;
        }
        $hiddenDocuments = $this->hidden;
        foreach ($hiddenDocuments as $hiddenDocument) {
            $queryComposer[] = '!'.$hiddenDocument['field'].':'.$hiddenDocument['value'];
        }

        return implode(' AND ', $queryComposer);
    }

    public function addFacets(FacetSet $facetSet, ?array $activeFacets): array
    {
        $facetConfiguration = $this->facets;

        $filterQueries = [];
        $facetCounter = $this->getFacetCounter($activeFacets);
        foreach ($facetConfiguration as $facet) {
            $facetSet
                ->createFacetField($facet['title'])
                ->setField($facet['field'])
                ->setMinCount(1)
                ->setLimit($facet['quantity'])
                ->setSort($facet['sort']);
        }

        $activeFacetCounter = is_array($activeFacets) ? count($activeFacets) : 0;

        if ($activeFacetCounter > 0) {
            foreach ($activeFacets as $activeFacet) {
                $filterQuery = new FilterQuery();
                foreach ($activeFacet as $itemKey => $item) {
                    $filterQuery->setKey($itemKey.$this->getFacetCounter($activeFacets).md5(microtime()));

                    if (preg_match('/\[\w* TO \w*\]/', $item)) {
                        $filterQuery->setQuery(vsprintf('%s:%s', [$itemKey, $item]));
                    } else {
                        $filterQuery->setQuery(vsprintf('%s:"%s"', [$itemKey, $item]));
                    }
                }
                $filterQueries[] = $filterQuery;
                ++$facetCounter;
            }
        }

        return $filterQueries;
    }

    public function getFacetCounter(?array $activeFacets): int
    {
        return is_array($activeFacets) ? count($activeFacets) : 0;
    }

    public function addQuerySort(Query $select, string $sort = '', string $order = '')
    {
        $sort = !empty($sort) && !empty($order) ? $this->getSorting($sort.' '.$order) : $this->getSorting();

        if (is_array($sort) && $sort != []) {
            $select->addSort($sort[0], $sort[1]);
        }
    }

    public function addQueryFilters(Query $select, ?array $activeFacets)
    {
        $facetSet = $select->getFacetSet();
        $filters = $this->addFacets($facetSet, $activeFacets);
        foreach ($filters as $filter) {
            $select->addFilterQuery($filter);
        }
    }
}
