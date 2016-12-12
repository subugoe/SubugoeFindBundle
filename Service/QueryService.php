<?php

namespace Subugoe\FindBundle\Service;

use Solarium\QueryType\Select\Query\Component\FacetSet;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;

/**
 * Services for query manipulation and extraction.
 */
class QueryService
{
    protected $defaultQuery;

    protected $defaultSort;

    protected $hidden;

    protected $facets;

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
     * @param string $sortString
     *
     * @return array $sort
     */
    public function getSorting($sortString = '')
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

    /**
     * @param string $query
     *
     * @return string $queryString
     */
    public function composeQuery($query)
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

        $queryString = implode(' AND ', $queryComposer);

        return $queryString;
    }

    /**
     * @param FacetSet $facetSet
     * @param array    $activeFacets
     *
     * @return array
     */
    public function addFacets(FacetSet $facetSet, $activeFacets)
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

        if (count($activeFacets) > 0) {
            foreach ($activeFacets as $activeFacet) {
                $filterQuery = new FilterQuery();
                foreach ($activeFacet as $itemKey => $item) {
                    $filterQuery->setKey($itemKey.$this->getFacetCounter($activeFacets).md5(microtime()));

                    if (preg_match('/\[[a-zA-Z0-9_]* TO [a-zA-Z0-9_]*\]/', $item)) {
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

    public function getFacetCounter($activeFacets)
    {
        $facetCounter = count($activeFacets) ?: 0;

        return $facetCounter;
    }

    /*
     * @param Query $select A Query instance
     */
    public function addQuerySort(Query $select, $sort = '', $order = '')
    {
        if (!empty($sort) && !empty($order)) {
            $sort = $this->getSorting($sort.' '.$order);
        } else {
            $sort = $this->getSorting();
        }

        if (is_array($sort) && $sort != []) {
            $select->addSort($sort[0], $sort[1]);
        }
    }

    /*
     * @param Query $select A Query instance
     * @param array $activeFacets Array of active facets
     */
    public function addQueryFilters(Query $select, $activeFacets)
    {
        $facetSet = $select->getFacetSet();
        $filters = $this->addFacets($facetSet, $activeFacets);
        foreach ($filters as $filter) {
            $select->addFilterQuery($filter);
        }
    }
}
