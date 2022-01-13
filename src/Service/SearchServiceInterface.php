<?php

namespace Subugoe\FindBundle\Service;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Solarium\Core\Query\DocumentInterface;
use Solarium\QueryType\Select\Query\Query;
use Subugoe\FindBundle\Entity\Search;

interface SearchServiceInterface
{
    public function addHighlighting(Query $select, string $field): Query;

    /**
     * @param array|null $fields a list of fields, i.e. ['id', 'title']
     */
    public function getDocumentBy(string $field, string $value, ?array $fields = []): DocumentInterface;

    public function getDocumentById(string $id, ?int $limit = 1): DocumentInterface;

    public function getHighlights(PaginationInterface $pagination, string $searchTerms): array;

    public function getLatestDocument(?string $dateField = 'date_indexed'): DocumentInterface;

    /**
     * @param Query $select A Query instance
     *
     * @return PaginationInterface $pagination A selected set of pages
     */
    public function getPagination(Query $select): PaginationInterface;

    public function getQuerySelect(): Query;

    public function getSearchEntity(): Search;
}
