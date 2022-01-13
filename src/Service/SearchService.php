<?php

declare(strict_types=1);

namespace Subugoe\FindBundle\Service;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Solarium\Client;
use Solarium\Core\Query\DocumentInterface;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;
use Subugoe\FindBundle\Entity\Search;
use Symfony\Component\HttpFoundation\RequestStack;

class SearchService implements SearchServiceInterface
{
    private Client $client;
    private array $configuration;
    private PaginatorInterface $paginator;
    private QueryService $queryService;
    private RequestStack $request;

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

    public function addHighlighting(Query $select, string $field): Query
    {
        $select->getHighlighting()->addField($field);

        return $select;
    }

    /**
     * @param array $fields a list of fields, i.e. ['id', 'title']
     */
    public function getDocumentBy(string $field, string $value, ?array $fields = []): DocumentInterface
    {
        $query = $this->client->createSelect();

        if (!empty($fields)) {
            $query->setFields($fields);
        }

        $query->setQuery(sprintf('%s:"%s"', $field, $value));
        $select = $this->client->select($query);
        $count = $select->count();

        if (0 === $count) {
            throw new \InvalidArgumentException(sprintf('Document with field %s and value %s not found', $field, $value));
        }

        return $select->getDocuments()[0];
    }

    public function getDocumentById(string $id, ?int $limit = 1): DocumentInterface
    {
        $query = $this->client->createSelect()
            ->setQuery(sprintf('id:%s', $id))
            ->setFields(['*', sprintf('[child parentFilter=id:%s limit=%d childFilter=work_id:%s]', $id, $limit, $id)]);
        $select = $this->client->select($query);
        $count = $select->count();

        if (0 === $count) {
            throw new \InvalidArgumentException(sprintf('Document %s not found', $id));
        }

        return $select->getDocuments()[0];
    }

    public function getHighlights(PaginationInterface $pagination, string $searchTerms): array
    {
        $highlights = [];

        if ([] !== $pagination && !empty($searchTerms)) {
            $docsToBeHighlighted = [];

            foreach ($pagination as $page) {
                $docsToBeHighlighted[] = $page->getId();
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

    public function getLatestDocument(?string $dateField = 'date_indexed'): DocumentInterface
    {
        $filter = (new FilterQuery())->setQuery('-doctype:fulltext +doctype:work')->setKey('doctype');
        $query = $this->client
            ->createSelect()
            ->setRows(1)
            ->addFilterQuery($filter)
            ->addSort($dateField, 'desc');
        $select = $this->client->select($query);
        $count = $select->count();

        if (0 === $count) {
            throw new \InvalidArgumentException(sprintf('Last scanned document not found'));
        }

        return $select->getDocuments()[0];
    }

    /**
     * @param Query $select A Query instance
     *
     * @return PaginationInterface $pagination A selected set of pages
     */
    public function getPagination(Query $select): PaginationInterface
    {
        return $this->paginator->paginate(
            [
                $this->client,
                $select,
            ],
            $this->getSearchEntity()->getCurrentPage(),
            $this->getSearchEntity()->getRows()
        );
    }

    public function getQuerySelect(): Query
    {
        $search = $this->getSearchEntity();
        $select = $this->client->createSelect();
        $select->setQuery($this->queryService->composeQuery($search->getQuery()));

        return $select;
    }

    public function getSearchEntity(): Search
    {
        $search = new Search();

        $scope = $this->request->getMasterRequest()->get('scope');
        $query = $this->request->getMasterRequest()->get('search')['q'] ?? '';

        if (!empty($query)) {
            if (false !== strpos($query, ':')) {
                $query = addcslashes($query, ':');
            }

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

    public function setConfig(array $config)
    {
        $this->configuration = $config;
    }

    private function getQuery(string $searchTerms, string $docId): string
    {
        $pageNumber = $this->configuration['snippet']['page_number'];
        $pageFulltext = $this->configuration['snippet']['page_fulltext'];

        if (strpos($searchTerms, ' ')) {
            $searchTerms = explode(' ', trim($searchTerms));
        }

        if (is_array($searchTerms) && [] !== $searchTerms) {
            $query = sprintf('%s:%s AND (', $pageNumber, $docId);

            foreach ($searchTerms as $key => $searchTerm) {
                if (str_contains($searchTerm, ':')) {
                    $searchTerm = addcslashes($searchTerm, ':');
                }

                if (0 === $key) {
                    $query .= sprintf('%s:%s', $pageFulltext, $searchTerm);
                } else {
                    $query .= sprintf(' OR %s:%s', $pageFulltext, $searchTerm);
                }
            }

            $query .= ')';
        } else {
            if (false !== strpos($searchTerms, ':')) {
                $searchTerms = addcslashes($searchTerms, ':');
            }

            $query = sprintf('%s:%s AND %s:%s', $pageNumber, $docId, $pageFulltext, $searchTerms);
        }

        return $query;
    }
}
