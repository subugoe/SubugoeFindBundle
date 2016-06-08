<?php

namespace Subugoe\FindBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solarium\QueryType\Select\Query\FilterQuery;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FeedController extends Controller
{
    /**
     * @Route("/feed/{_format}/{category}", name="_feed", defaults={"category" = ""})
     */
    public function feedAction($_format, $category)
    {
        $client = $this->get('solarium.client');

        $select = $client
            ->createSelect()
            ->setQuery($this->getParameter('default_query'))
            ->setFields($this->getParameter('feed_fields'))
            ->setRows($this->getParameter('feed_rows'));

        $sort = $this->get('subugoe_find.query_service')->getSorting($this->getParameter('feed_sort'));

        if (!empty($category)) {
            $categoryField = $this->getParameter('feed_category');

            $categoryFilter = new FilterQuery();
            $categoryFilter
                ->setKey('category')
                ->setQuery(vsprintf('%s:%s', [$categoryField, $category]));
            $select->addFilterQuery($categoryFilter);
        }

        if (is_array($sort) && $sort != []) {
            $select->addSort($sort[0], $sort[1]);
        }

        $feeds = $client->select($select);
        $template = sprintf('SubugoeFindBundle:Default:feed.%s.twig', $_format);

        return $this->render($template, [
            'feeds' => $feeds,
            'category' => $category,
        ]);
    }
}
