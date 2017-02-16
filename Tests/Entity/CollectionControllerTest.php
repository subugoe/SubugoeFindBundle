<?php

namespace Subugoe\FindBundle\Tests\Entity;

use Subugoe\FindBundle\Entity\Search;

class CollectionControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Search
     */
    protected $fixture;

    public function counterProvider()
    {
        return [
            [
                1, 1, 0,
            ],
            [
                1, 20, 0,
            ],
            [
                2, 20, 20,
            ],
            [
                100, 30, 2970,
            ],
        ];
    }

    public function setUp()
    {
        $this->fixture = new Search();
    }

    /**
     * @dataProvider counterProvider
     */
    public function testIfOffsetIsCorrectlyCalculated($currentPage, $rows, $expected)
    {
        $this->fixture->setCurrentPage($currentPage);
        $this->fixture->setRows($rows);
        $result = $this->fixture->getOffset();
        $this->assertSame($expected, $result);
    }
}
