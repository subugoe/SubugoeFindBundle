<?php

declare(strict_types=1);

namespace Subugoe\FindBundle\Service;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Solarium\Client;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\DocumentInterface;
use Subugoe\FindBundle\Entity\Search;
use Subugoe\IIIFBundle\Model\Document;
use Symfony\Component\HttpFoundation\RequestStack;

class SearchService
{
    /**
     * @var RequestStack
     */
    private $request;

    /**
     * @var int
     */
    private $resultsPerPage;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var QueryService
     */
    private $queryService;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var array
     */
    private $snippetConfig;

    public function __construct(
        RequestStack $request,
        Client $client,
        QueryService $queryService,
        PaginatorInterface $paginator,
        int $resultsPerPage,
        array $snippetConfig
    ) {
        $this->request = $request;
        $this->resultsPerPage = $resultsPerPage;
        $this->client = $client;
        $this->queryService = $queryService;
        $this->paginator = $paginator;
        $this->snippetConfig = $snippetConfig;
    }

    /**
     * @return Search
     */
    public function getSearchEntity(): Search
    {
        $search = new Search();

        $scope = $this->request->getMasterRequest()->get('scope');
        $query = $this->request->getMasterRequest()->get('search')['q'];
        if (!empty($query)) {
            if (!empty($scope)) {
                $search->setQuery(sprintf('%s:%s', $scope, $query));
            } else {
                $search->setQuery(sprintf('%s:%s', $this->request->getMasterRequest()->get('search')['searchType'],
                    $query));
            }
        }
        $search
            ->setRows((int) $this->resultsPerPage)
            ->setCurrentPage((int) $this->request->getMasterRequest()->get('page') ?: 1);

        return $search;
    }

    /**
     * @param Query $select A Query instance
     *
     * @return array $pagination A selected set of pages
     */
    public function getPagination(Query $select): PaginationInterface
    {
        $pagination = $this->paginator->paginate(
            [
                $this->client,
                $select,
            ],
            $this->getSearchEntity()->getCurrentPage(),
            $this->getSearchEntity()->getRows()
        );

        return $pagination;
    }

    /**
     * @return Query $select A Query instance
     */
    public function getQuerySelect(): Query
    {
        $search = $this->getSearchEntity();
        $select = $this->client->createSelect();
        $select
            ->setQuery($this->queryService->composeQuery($search->getQuery()));

        return $select;
    }

    /**
     * @param Query  $select
     * @param string $field
     *
     * @return Query
     */
    public function addHighlighting(Query $select, string $field): Query
    {
        $select->getHighlighting()->addField($field);

        return $select;
    }

    /**
     * @param string $id
     *
     * @return DocumentInterface
     */
    public function getDocumentById(string $id): DocumentInterface
    {
        $select = $this->client->createSelect();

        $select->setQuery(sprintf('id:%s', $id));
        $document = $this->client->select($select);
        $document = $document->getDocuments();

        if (count($document) === 0) {
            throw new \InvalidArgumentException(sprintf('Document %s not found', $id));
        }

        return $document[0];
    }

    /**
     * @param string $field
     * @param string $value
     * @param array  $fields a list of fields, i.e. ['id', 'title']
     *
     * @return \Solarium\QueryType\Select\Result\DocumentInterface
     */
    public function getDocumentBy(string $field, string $value, array $fields = []): DocumentInterface
    {
        $select = $this->client
            ->createSelect();

        if (!empty($fields)) {
            $select->setFields($fields);
        }

        $select->setQuery(sprintf('%s:"%s"', $field, $value));
        $document = $this->client->select($select);
        $document = $document->getDocuments();

        if (count($document) === 0) {
            throw new \InvalidArgumentException(sprintf('Document with field %s and value %s not found', $field, $value));
        }

        return $document[0];
    }

    /**
     * @param PaginationInterface $pagination
     * @param string              $searchTerms
     *
     * @return array $highlightss
     */
    public function getHighlights(PaginationInterface $pagination, string $searchTerms): array
    {
        $highlights = [];

        if ($pagination !== [] && !empty($searchTerms)) {
            $docsToBeHighlighted = [];

            foreach ($pagination as $page) {
                $docsToBeHighlighted[] = $page->id;
            }

            if (strpos($searchTerms, ' ')) {
                $searchTerms = explode(' ', trim($searchTerms));
            }

            foreach ($docsToBeHighlighted as $docId) {
                $select = $this->client->createSelect();
                $pageNumber = $this->snippetConfig['page_number'];
                $pageFulltext = $this->snippetConfig['page_fulltext'];

                if (is_array($searchTerms) && $searchTerms !== []) {
                    $query = sprintf('%s:%s AND (', $pageNumber, $docId);

                    foreach ($searchTerms as $key => $searchTerm) {
                        if ($key === 0) {
                            $query .= sprintf('%s:%s', $pageFulltext, $searchTerm);
                        } else {
                            $query .= sprintf(' OR %s:%s', $pageFulltext, $searchTerm);
                        }
                    }

                    $query .= ')';
                } else {
                    $query = sprintf('%s:%s AND %s:%s', $pageNumber, $docId, $pageFulltext, $searchTerms);
                }

                $select->setQuery($query);

                $snippetCount = $this->client->select($select)->getNumFound();

                $select->setRows($snippetCount)->addSort($this->snippetConfig['sort'], $this->snippetConfig['sort_dir']);

                $select->getHighlighting()
                        ->setFields($this->snippetConfig['field'])
                        ->setSnippets($this->snippetConfig['count'])
                        ->setFragSize($this->snippetConfig['length'])
                        ->setSimplePrefix($this->snippetConfig['prefix'])
                        ->setSimplePostfix($this->snippetConfig['postfix']);

                $resultSet = $this->client->select($select);
                $highlighting = $resultSet->getHighlighting();

                foreach ($resultSet as $key => $document) {
                    $doc = $highlighting->getResult($document->id);
                    $snippets = $doc->getField($this->snippetConfig['field']);
                    $highlights[$docId][$key] = ['pageNumber' => $document->ft_page_number, 'snippets' => $snippets];
                }
            }
        }

        return $highlights;
    }
}
