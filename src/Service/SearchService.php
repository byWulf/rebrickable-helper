<?php
declare(strict_types=1);

namespace App\Service;

use App\Client\SearchClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class SearchService
{
    public function __construct(private SearchClient $searchClient)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function getResult(string $query): array
    {
        return $this->searchClient->searchForParts($query);
    }
}
