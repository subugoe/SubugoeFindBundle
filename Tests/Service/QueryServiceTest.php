<?php

namespace Subugoe\FindBundle\Tests\Service;

use Subugoe\FindBundle\Service\QueryService;

/**
 * Unit tests for QueryService methods.
 */
class QueryServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueryService
     */
    protected $fixture;

    public function sortingProvider()
    {
        return [
            [
                'a b', 2,
            ],
            [
                'a b c', 3,
            ],
        ];
    }

    public function setUp()
    {
        $this->fixture = new QueryService('a', 'b', 'c', 'd');
    }

    public function testSortingReturnsAnEmptyArrayWhenThereIsNoWhitespaceInTheString()
    {
        $this->assertSameSize([], $this->fixture->getSorting());
    }

    /**
     * @dataProvider sortingProvider
     */
    public function testSortingReturnsAnArrayWithTheCorrectNumberOfElements($sortString, $count)
    {
        $this->assertSame($count, count($this->fixture->getSorting($sortString)));
    }
}
