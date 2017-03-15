<?php

namespace Subugoe\FindBundle\Service;

use Knp\Component\Pager\PaginatorInterface;
use Solarium\Client;
use Solarium\QueryType\Select\Query\Query;
use Subugoe\FindBundle\Entity\Search;
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

    public function __construct(RequestStack $request, Client $client, QueryService $queryService, PaginatorInterface $paginator, int $resultsPerPage)
    {
        $this->request = $request;
        $this->resultsPerPage = $resultsPerPage;
        $this->client = $client;
        $this->queryService = $queryService;
        $this->paginator = $paginator;
    }

    /**
     * @return Search
     */
    public function getSearchEntity()
    {
        $search = new Search();

        $scope = $this->request->getMasterRequest()->get('scope');

        if (!empty($scope)) {
            $search->setQuery($scope.':'.$this->request->getMasterRequest()->get('q'));
        } else {
            $search->setQuery($this->request->getMasterRequest()->get('q'));
        }

        $search
            ->setRows((int) $this->resultsPerPage)
            ->setCurrentPage((int) $this->request->getMasterRequest()->get('page') ?: 1);

        return $search;
    }

    /*
     * @param Query $select A Query instance
     *
     * @return array $pagination A selected set of pages
     */
    public function getPagination(Query $select)
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

     /*
      * @return Query $select A Query instance
      */
    public function getQuerySelect()
    {
        $search = $this->getSearchEntity();
        $select = $this->client->createSelect();
        $select->setQuery($this->queryService->composeQuery($search->getQuery()));

        return $select;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getDocumentById(string $id)
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
     * @param $field
     * @param $value
     * @return \Solarium\QueryType\Select\Result\DocumentInterface
     */
    public function getDocumentBy(string $field, string $value)
    {
        $select = $this->client->createSelect();

        $select->setQuery(sprintf('%s:"%s"', $field, $value));
        $document = $this->client->select($select);
        $document = $document->getDocuments();

        if (count($document) === 0) {
            throw new \InvalidArgumentException(sprintf('Document with field %s and value %s not found', $field, $value));
        }

        return $document[0];
    }
}
