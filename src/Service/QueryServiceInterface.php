<?php

namespace Subugoe\FindBundle\Service;

use Solarium\Component\FacetSet;
use Solarium\QueryType\Select\Query\Query;

/**
 * Services for query manipulation and extraction.
 */
interface QueryServiceInterface
{
    public function addFacets(FacetSet $facetSet, ?array $activeFacets): array;

    public function addQueryFilters(Query $select, ?array $activeFacets);

    public function addQuerySort(Query $select, ?string $sort = '', ?string $order = '');

    public function composeQuery(?string $query): string;

    public function getFacetCounter(?array $activeFacets): int;

    /**
     * Returns the sorting part of the query.
     */
    public function getSorting(?string $sortString = ''): array;
}
