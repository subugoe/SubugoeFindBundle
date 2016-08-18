<?php

namespace Subugoe\FindBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Subugoe\FindBundle\Entity\Search;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Solarium\QueryType\Select\Query\Query;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="_homepage")
     *
     * @param Request $request A request instance
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $search = $this->getSearchEntity($request);
        $select = $this->getQuerySelect($request);
        $this->addQuerySort($select);
        $activeFacets = $request->get('filter');
        $this->addQueryFilters($select, $activeFacets);
        $pagination = $this->getPagination($request, $select);
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
    public function detailAction($id)
    {
        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('id:'.$id);
        $document = $client->select($select);
        $document = $document->getDocuments();
        if (count($document) === 0) {
            throw new NotFoundHttpException(sprintf('Document %s not found', $id));
        }

        return $this->render('SubugoeFindBundle:Default:detail.html.twig', ['document' => $document[0]->getFields()]);
    }

    /*
     * @param Request $request A request instance
     *
     * @return Search $search A Search entity instance
     */
    protected function getSearchEntity(Request $request)
    {
        $search = new Search();
        $search
            ->setQuery($request->get('q'))
            ->setRows((int) $this->getParameter('results_per_page'))
            ->setCurrentPage((int) $request->get('page') ?: 1);

        return $search;
    }

    /*
     * @param Request $request A request instance
     *
     * @return Query $select A Query instance
     */
    protected function getQuerySelect(Request $request)
    {
        $client = $this->get('solarium.client');
        $queryService = $this->get('subugoe_find.query_service');
        $search = $this->getSearchEntity($request);
        $select = $client->createSelect();
        $select->setQuery($queryService->composeQuery($search->getQuery()));

        return $select;
    }

    /*
     * @param Query $select A Query instance
     */
    protected function addQuerySort(Query $select)
    {
        $queryService = $this->get('subugoe_find.query_service');
        $sort = $queryService->getSorting();
        if (is_array($sort) && $sort != []) {
            $select->addSort($sort[0], $sort[1]);
        }
    }

    /*
     * @param Query $select A Query instance
     * @param array $activeFacets Array of active facets
     */
    protected function addQueryFilters(Query $select, $activeFacets)
    {
        $queryService = $this->get('subugoe_find.query_service');
        $facetSet = $select->getFacetSet();
        $filters = $queryService->addFacets($facetSet, $activeFacets);
        foreach ($filters as $filter) {
            $select->addFilterQuery($filter);
        }
    }

    /*
     * @param Request $request A request instance
     * @param Query $select A Query instance
     *
     * @return array $pagination A selected set of pages
     */
    protected function getPagination(Request $request, Query $select)
    {
        $pagination = $this->get('knp_paginator')->paginate(
            [
                $this->get('solarium.client'),
                $select,
            ],
            $this->getSearchEntity($request)->getCurrentPage(),
            $this->getSearchEntity($request)->getRows()
        );

        return $pagination;
    }
}
