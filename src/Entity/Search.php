<?php

namespace Subugoe\FindBundle\Entity;

/**
 * Entity for common search metadata.
 */
class Search
{
    /**
     * @var string
     */
    protected $query;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var int
     */
    protected $rows;

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     *
     * @return Search
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return ($this->getCurrentPage() - 1) * $this->getRows();
    }

    /**
     * @param int $offset
     *
     * @return Search
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param int $rows
     *
     * @return Search
     */
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     *
     * @return Search
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;

        return $this;
    }
}
