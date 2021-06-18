<?php
declare(strict_types=1);

namespace App\Dto;

class SearchDto
{
    private string $query;

    public function setQuery(string $query): SearchDto
    {
        $this->query = $query;
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
