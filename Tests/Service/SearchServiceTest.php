<?php

namespace Subugoe\FindBundle\Tests\Service;

use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Subugoe\FindBundle\Service\QueryService;
use Subugoe\FindBundle\Service\SearchService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for SearchService methods.
 */
class SearchServiceTest extends TestCase
{
    /**
     * @var SearchService
     */
    protected $fixture;

    public function setUp()
    {
        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $client = $this->getMockBuilder(Client::class)->getMock();
        $queryService = new QueryService('a', 'b', 'c', 'd');
        $paginator = new Paginator(new EventDispatcher());
        $snippetConfig = [
            'page_number' => 43,
            'page_fulltext' => 3,
        ];
        $this->fixture = new SearchService($requestStack, $client, $queryService, $paginator, 30, $snippetConfig);
    }

    public function testTrimmingOfTheSearchQuery()
    {
        $this->markTestIncomplete('Solr has to be mocked');
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());

        $items = function () {
            $classes = [];
            foreach (range(1, 23) as $id) {
                $class = new \stdClass();
                $class->id = $id;
                $classes[] = $class;
            }

            return $classes;
        };

        $pagination = new Paginator($dispatcher);
        $view = $pagination->paginate($items(), 1, 10);

        $this->assertEmpty($this->fixture->getHighlights($view, 'a'));
    }
}
