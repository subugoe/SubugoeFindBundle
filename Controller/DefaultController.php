<?php

namespace Subugoe\FindBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $client = $this->get('solarium.client');
        $select = $client->createSelect();
        $select->setQuery('iswork:true');
        $results = $client->select($select);

        return $this->render('SubugoeFindBundle:Default:index.html.twig', ['results' => $results]);
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

        return $this->render('SubugoeFindBundle:Default:detail.html.twig', ['document' => $document->getDocuments()]);
    }

}
