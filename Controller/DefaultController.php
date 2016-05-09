<?php

namespace Subugoe\FindBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('SubugoeFindBundle:Default:index.html.twig');
    }
}
