<?php
declare(strict_types=1);

namespace App\Client;

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BricksAndPiecesLegoClient
{
    public function __construct(
        private HttpClientInterface $bricksAndPiecesLegoClient,
        private CacheInterface $arrayRedisCache,
    )
    {
    }

    public function getPartsBySet(string $setId): array
    {
        return $this->arrayRedisCache->get('lego_client.getPartsBySet.' . $setId, function() use ($setId): array {
            $response = $this->bricksAndPiecesLegoClient->request(
                'GET',
                '/api/v1/bricks/product/' . $setId . '?country=DE&orderType=buy',
                [
                    'headers' => [
                        'x-api-key' => 'saVSCq0hpuxYV48mrXMGfdKnMY1oUs3s',
                    ]
                ]
            );

            try {
                return $response->toArray(true);
            } catch (ClientException|JsonException) {
                return ['bricks' => []];
            }
        });
    }

}
