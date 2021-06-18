<?php
declare(strict_types=1);

namespace App\Client;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SearchClient
{
    public function __construct(private HttpClientInterface $rebrickableClient)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function searchForParts(string $query): array
    {
        $response = $this->rebrickableClient->request(
            'GET',
            '/api/v3/lego/parts', [
                'query' => [
                    'page_size' => 1000,
                    'search' => $query,
                ],
            ],
        );

        return $response->toArray(true);
    }
}
