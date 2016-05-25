<?php

namespace Subugoe\FindBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solarium\QueryType\Select\Query\FilterQuery;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {

        $query = $request->get('q');

        $facetConfiguration = $this->getParameter('facets');

        $client = $this->get('solarium.client');
        $select = $client->createSelect();

        $queryString = $this->composeQuery($query);

        $select->setQuery($queryString);

        $sort = $this->getSorting();

        if (is_array($sort)) {
            $select->addSort($sort[0], $sort[1]);
        }

        $facetSet = $select->getFacetSet();
        foreach ($facetConfiguration as $facet) {
            $facetSet->createFacetField($facet['title'])->setField($facet['field']);
        }

        $activeFacets = $request->get('filter');

        $facetCounter = count($activeFacets) ?: 0;

        if (count($activeFacets) > 0) {

            foreach ($activeFacets as $activeFacet) {
                $filterQuery = new FilterQuery();

                foreach ($activeFacet as $itemKey => $item) {
                    $filterQuery->setKey($itemKey . $facetCounter);
                    $filterQuery->setQuery($itemKey . ':"' . $item . '"');
                }

                $select->addFilterQuery($filterQuery);
                ++$facetCounter;

            }

        }

        $results = $client->select($select);

        $paginator  = $this->get('knp_paginator');
        $rows = (int) $this->getParameter('results_per_page');
        $currentPage = (int)$request->get('page') ?: 1;

        $pagination = $paginator->paginate(
            [
                $client,
                $select
            ],
            $currentPage,
            $rows
        );

        $offset = ($currentPage - 1) * $rows;

        return $this->render('SubugoeFindBundle:Default:index.html.twig', [
            'facets' => $results->getFacetSet()->getFacets(),
            'facetCounter' => $facetCounter,
            'queryParams' => $request->get('filter') ?: [],
            'query' => $query,
            'pagination' => $pagination,
            'offset' => $offset,
        ]);
    }

    /**
     * @Route("/id/{id}", name="_detail")
     * @return string
     */
    public function detailAction($id)
    {

        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('id:' . $id);
        $document = $client->select($select);
        $document = $document->getDocuments();

        if (count($document) === 0) {
            throw new NotFoundHttpException(sprintf('Document %s not found', $id));
        }

        return $this->render('SubugoeFindBundle:Default:detail.html.twig', ['document' => $document[0]->getFields()]);
    }

    /**
     * @param $query
     * @return string
     */
    protected function composeQuery($query)
    {
        $queryComposer = [];
        $queryComposer[] = $this->getParameter('default_query');

        if (!empty($query)) {
            $queryComposer[] = $query;
        }

        $hiddenDocuments = $this->getParameter('hidden');

        foreach ($hiddenDocuments as $hiddenDocument) {
            $queryComposer[] = '!' . $hiddenDocument['field'] . ':' . $hiddenDocument['value'];
        }

        $queryString = join(' AND ', $queryComposer);
        return $queryString;
    }

    protected function getSorting()
    {
        $sort = $this->getParameter('default_sort');
        if (preg_match('/\s/', $sort)) {
            $sort = explode(' ', $sort);
        }

        return $sort;
    }

}
