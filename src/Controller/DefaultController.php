<?php

declare(strict_types=1);

namespace Subugoe\FindBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Solarium\Client;
use Subugoe\FindBundle\Service\QueryService;
use Subugoe\FindBundle\Service\SearchService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends AbstractFOSRestController
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var QueryService
     */
    private $queryService;

    /**
     * @var SearchService
     */
    private $searchService;

    public function __construct(Client $client, QueryService $queryService, SearchService $searchService)
    {
        $this->client = $client;
        $this->queryService = $queryService;
        $this->searchService = $searchService;
    }

    /**
     * @Route("/", name="_homepage")
     *
     * @param Request $request A request instance
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $search = $this->searchService->getSearchEntity();
        $select = $this->searchService->getQuerySelect();
        $this->queryService->addQuerySort($select);
        $activeFacets = $request->get('filter');
        $this->queryService->addQueryFilters($select, $activeFacets);
        $pagination = $this->searchService->getPagination($select);

        $facets = $this->client->select($select)->getFacetSet()->getFacets();
        $facetCounter = $this->queryService->getFacetCounter($activeFacets);

        return $this->render('SubugoeFindBundle:Default:index.html.twig', [
                    'facets' => $facets,
                    'facetCounter' => $facetCounter,
                    'queryParams' => $request->get('filter') ?: [],
                    'search' => $search,
                    'pagination' => $pagination,
                ]);
    }

    /**
     * @Route("/id/{id}", name="_detail")
     *
     * @param string $id The document id
     *
     * @return Response
     */
    public function detailAction(string $id, Request $request): Response
    {
        $select = $this->client->createSelect();
        $select->setQuery(sprintf('id:%s', $id));
        $document = $this->client->select($select);
        $document = $document->getDocuments();
        if (0 === count($document)) {
            throw new NotFoundHttpException(sprintf('Document %s not found', $id));
        }

        return $this->render('SubugoeFindBundle:Default:detail.html.twig', ['document' => $document[0]->getFields()]);
    }
}