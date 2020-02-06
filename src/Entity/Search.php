<?php

namespace Subugoe\FindBundle\Entity;

/**
 * Entity for common search metadata.
 */
class Search
{
    protected int $currentPage;
    protected int $offset;
    protected $query;
    protected int $rows;

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getOffset(): int
    {
        return ($this->getCurrentPage() - 1) * $this->getRows();
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function setCurrentPage(int $currentPage): self
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function setRows(int $rows): self
    {
        $this->rows = $rows;

        return $this;
    }
}
