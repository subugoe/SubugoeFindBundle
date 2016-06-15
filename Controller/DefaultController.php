<?php

namespace Subugoe\FindBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Subugoe\FindBundle\Entity\Search;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="_homepage")
     */
    public function indexAction(Request $request)
    {
        $client = $this->get('solarium.client');
        $queryService = $this->get('subugoe_find.query_service');

        $search = new Search();
        $search
            ->setQuery($request->get('q'))
            ->setRows((int) $this->getParameter('results_per_page'))
            ->setCurrentPage((int) $request->get('page') ?: 1);

        $activeFacets = $request->get('filter');

        $select = $client->createSelect();
        $select->setQuery($queryService->composeQuery($search->getQuery()));
        $sort = $queryService->getSorting();

        if (is_array($sort) && $sort != []) {
            $select->addSort($sort[0], $sort[1]);
        }

        $facetSet = $select->getFacetSet();

        $filters = $queryService->addFacets($facetSet, $activeFacets);

        foreach ($filters as $filter) {
            $select->addFilterQuery($filter);
        }

        $pagination = $this->get('knp_paginator')->paginate(
            [
                $client,
                $select,
            ],
            $search->getCurrentPage(),
            $search->getRows()
        );

        return $this->render('SubugoeFindBundle:Default:index.html.twig', [
            'facets' => $client->select($select)->getFacetSet()->getFacets(),
            'facetCounter' => $queryService->getFacetCounter($activeFacets),
            'queryParams' => $request->get('filter') ?: [],
            'search' => $search,
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/id/{id}", name="_detail")
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
}
