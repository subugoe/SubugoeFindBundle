<?php

declare(strict_types=1);

namespace Subugoe\FindBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController as Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="_homepage")
     *
     * @param Request $request A request instance
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $searchService = $this->get('subugoe_find.search_service');
        $queryService = $this->get('subugoe_find.query_service');

        $search = $searchService->getSearchEntity();
        $select = $searchService->getQuerySelect();
        $queryService->addQuerySort($select);
        $activeFacets = $request->get('filter');
        $queryService->addQueryFilters($select, $activeFacets);
        $pagination = $searchService->getPagination($select);

        $facets = $this->get('solarium.client')->select($select)->getFacetSet()->getFacets();
        $facetCounter = $this->get('subugoe_find.query_service')->getFacetCounter($activeFacets);

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
        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery(sprintf('id:%s', $id));
        $document = $client->select($select);
        $document = $document->getDocuments();
        if (count($document) === 0) {
            throw new NotFoundHttpException(sprintf('Document %s not found', $id));
        }

        return $this->render('SubugoeFindBundle:Default:detail.html.twig', ['document' => $document[0]->getFields()]);
    }
}
