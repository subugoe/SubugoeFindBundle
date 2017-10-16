<?php

declare(strict_types=1);

namespace Subugoe\FindBundle\Service;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Solarium\Client;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\DocumentInterface;
use Subugoe\FindBundle\Entity\Search;
use Symfony\Component\HttpFoundation\RequestStack;

class SearchService
{
    /**
     * @var RequestStack
     */
    private $request;

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
    private $configuration;

    public function __construct(
        RequestStack $request,
        Client $client,
        QueryService $queryService,
        PaginatorInterface $paginator
    ) {
        $this->request = $request;
        $this->client = $client;
        $this->queryService = $queryService;
        $this->paginator = $paginator;
    }

    public function setConfig(array $config)
    {
        $this->configuration = $config;
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
            ->setRows((int) $this->configuration['results_per_page'])
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
        $select->setFields(['*', sprintf('[child parentFilter=id:%s limit=300]', $id)]);
        $document = $this->client->select($select);
        $document = $document->getDocuments();

        if (0 === count($document)) {
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

        if (0 === count($document)) {
            throw new \InvalidArgumentException(sprintf('Document with field %s and value %s not found', $field,
                $value));
        }

        return $document[0];
    }

    /**
     * @param PaginationInterface $pagination
     * @param string              $searchTerms
     *
     * @return array $highlights
     */
    public function getHighlights(PaginationInterface $pagination, string $searchTerms): array
    {
        $highlights = [];

        if ($pagination !== [] && !empty($searchTerms)) {
            $docsToBeHighlighted = [];

            foreach ($pagination as $page) {
                $docsToBeHighlighted[] = $page->getId();
            }

            if (strpos($searchTerms, ' ')) {
                $searchTerms = explode(' ', trim($searchTerms));
            }

            foreach ($docsToBeHighlighted as $docId) {
                $select = $this->client->createSelect();

                $select->setQuery($this->getQuery($searchTerms, $docId));

                $snippetCount = $this->client->select($select)->getNumFound();

                $select->setRows($snippetCount)->addSort($this->configuration['snippet']['sort'],
                    $this->configuration['snippet']['sort_dir']);

                $select->getHighlighting()
                    ->setFields($this->configuration['snippet']['field'])
                    ->setSnippets($this->configuration['snippet']['count'])
                    ->setFragSize($this->configuration['snippet']['length'])
                    ->setSimplePrefix($this->configuration['snippet']['prefix'])
                    ->setSimplePostfix($this->configuration['snippet']['postfix']);

                $resultSet = $this->client->select($select);
                $highlighting = $resultSet->getHighlighting();

                foreach ($resultSet as $key => $document) {
                    $doc = $highlighting->getResult($document->id);
                    $snippets = $doc->getField($this->configuration['snippet']['field']);
                    $highlights[$docId][$key] = ['pageNumber' => $document->ft_page_number, 'snippets' => $snippets];
                }
            }
        }

        return $highlights;
    }

    public function getLatestDocument(string $dateField = 'date_indexed')
    {
        $filter = (new FilterQuery())->setQuery('-doctype:fulltext +doctype:work')->setKey('doctype');
        $select = $this->client
            ->createSelect()
            ->setRows(1)
            ->addFilterQuery($filter)
            ->addSort($dateField, 'desc');

        return $this->client->select($select)->getDocuments()[0];
    }

    /**
     * @param string $searchTerms
     * @param $docId
     *
     * @return string
     */
    private function getQuery(string $searchTerms, $docId): string
    {
        $pageNumber = $this->configuration['snippet']['page_number'];
        $pageFulltext = $this->configuration['snippet']['page_fulltext'];

        if (is_array($searchTerms) && $searchTerms !== []) {
            $query = sprintf('%s:%s AND (', $pageNumber, $docId);

            foreach ($searchTerms as $key => $searchTerm) {
                if (0 === $key) {
                    $query .= sprintf('%s:%s', $pageFulltext, $searchTerm);
                } else {
                    $query .= sprintf(' OR %s:%s', $pageFulltext, $searchTerm);
                }
            }

            $query .= ')';
        } else {
            $query = sprintf('%s:%s AND %s:%s', $pageNumber, $docId, $pageFulltext, $searchTerms);
        }

        return $query;
    }
}
