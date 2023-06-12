<?php

namespace Subugoe\FindBundle\Tests\Service;

use Knp\Component\Pager\ArgumentAccess\RequestArgumentAccess;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\MockObject\Exception;
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
    protected SearchService $fixture;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $client = $this->createMock(Client::class);
        $queryService = new QueryService('a', 'b', (array) 'c', (array) 'd');
        $paginator = new Paginator(new EventDispatcher(), new RequestArgumentAccess($requestStack));
        $snippetConfig = [
            'page_number' => 43,
            'page_fulltext' => 3,
        ];
        $this->fixture = new SearchService($requestStack, $client, $queryService, $paginator, 30, $snippetConfig);
    }

    public function testTrimmingOfTheSearchQuery(): void
    {
        $this->markTestIncomplete('Solr has to be mocked');
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());

        $items = static function () {
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
