<?php

namespace Subugoe\FindBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solarium\QueryType\Select\Query\FilterQuery;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {

        $query = $request->get('q');

        $facetConfiguration = $this->getParameter('facets');

        $client = $this->get('solarium.client');
        $select = $client->createSelect();

        if (empty($query)) {
            $select->setQuery('iswork:true');
        } else {
            $select->setQuery($query);
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

        return $this->render('SubugoeFindBundle:Default:index.html.twig', [
            'results' => $results,
            'facets' => $results->getFacetSet()->getFacets(),
            'facetCounter' => $facetCounter,
            'queryParams' => $request->get('filter') ?: [],
            'query' => $query,
        ]);
    }

    /**
     * @Route("/id/{id}", name="_detail")
     * @return string
     */
    public function detailAction(Request $request)
    {

        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('id:' . $request->get('id'));
        $document = $client->select($select);
        $document = $document->getDocuments();

        return $this->render('SubugoeFindBundle:Default:detail.html.twig', ['document' => $document[0]->getFields()]);
    }

}
