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
}
