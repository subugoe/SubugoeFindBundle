<?php

declare(strict_types=1);

namespace Subugoe\FindBundle\Controller;

use Solarium\Client;
use Solarium\QueryType\Select\Query\FilterQuery;
use Subugoe\FindBundle\Service\QueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FeedController extends AbstractController
{
    public function __construct(private Client $client, private QueryService $queryService)
    {
    }

    /**
     * @Route("/feed/{_format}/{category}", name="_feed", defaults={"category" = ""})
     */
    public function feedAction(string $_format, string $category = ''): Response
    {
        $select = $this->client
            ->createSelect()
            ->setQuery($this->getParameter('default_query'))
            ->setFields($this->getParameter('feed_fields'))
            ->setRows($this->getParameter('feed_rows'));

        $sort = $this->queryService->getSorting($this->getParameter('feed_sort'));

        $workFilter = new FilterQuery();
        $workFilter->setKey('work')->setQuery('doctype:work');
        $select->addFilterQuery($workFilter);

        if (!empty($category)) {
            $categoryField = $this->getParameter('feed_category');

            $categoryFilter = new FilterQuery();
            $categoryFilter
                ->setKey('category')
                ->setQuery(vsprintf('%s:%s', [$categoryField, $category]));
            $select->addFilterQuery($categoryFilter);
        }

        if ([] !== $sort) {
            $select->addSort($sort[0], $sort[1]);
        }

        $feeds = $this->client->select($select);
        $template = sprintf('@SubugoeFind/Default/feed.%s.twig', $_format);

        return $this->render($template, [
            'feeds' => $feeds,
            'category' => $category,
        ]);
    }
}
