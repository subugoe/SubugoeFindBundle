<?php

namespace Subugoe\FindBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Subugoe\FindBundle\Service\QueryService;

/**
 * Unit tests for QueryService methods.
 */
class QueryServiceTest extends TestCase
{
    protected QueryService $fixture;

    public function setUp(): void
    {
        $this->fixture = new QueryService('a', 'b', (array) 'c', (array) 'd');
    }

    public function sortingProvider(): array
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

    /**
     * @dataProvider sortingProvider
     */
    public function testSortingReturnsAnArrayWithTheCorrectNumberOfElements($sortString, $count): void
    {
        $this->assertCount($count, $this->fixture->getSorting($sortString));
    }

    public function testSortingReturnsAnEmptyArrayWhenThereIsNoWhitespaceInTheString(): void
    {
        $this->assertSameSize([], $this->fixture->getSorting());
    }
}
