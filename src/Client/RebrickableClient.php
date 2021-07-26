<?php
declare(strict_types=1);

namespace App\Client;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RebrickableClient
{
    private ?string $token = 'd5bed098dc582c585b29a399b4bcc308d7ca9bbe14f4e62088c2c092e700b970';

    public function __construct(
        private HttpClientInterface $rebrickableClient,
        private CacheInterface $arrayRedisCache,
        private string $username,
        private string $password,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function searchForParts(string $query): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.searchForParts.' . $query, function() use ($query): array {
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
        });
    }

    public function getPart(string $partNumber): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.getPart.' . $partNumber, function() use ($partNumber): array {
            $response = $this->rebrickableClient->request(
                'GET',
                '/api/v3/lego/parts/' . $partNumber,
            );

            return $response->toArray(true);
        });
    }

    /**
     * @throws ExceptionInterface
     */
    public function getParts(array $partNumbers): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.getParts.' . implode(',', $partNumbers), function() use ($partNumbers): array {
            $response = $this->rebrickableClient->request(
                'GET',
                '/api/v3/lego/parts', [
                'query' => [
                    'page_size' => 1000,
                    'part_nums' => implode(',', $partNumbers),
                    'inc_part_details' => '1',
                    'inc_color_details' => '1',
                ],
            ],
            );

            return $response->toArray(true);
        });
    }

    /**
     * @throws ExceptionInterface
     */
    public function getPartsByBricklinkId(string $bricklinkId): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.getPartsByBricklinkId.' . $bricklinkId, function() use ($bricklinkId): array {
            $response = $this->rebrickableClient->request(
                'GET',
                '/api/v3/lego/parts', [
                'query' => [
                    'page_size' => 1000,
                    'bricklink_id' => $bricklinkId,
                    'inc_part_details' => '1',
                    'inc_color_details' => '1',
                ],
            ],
            );

            return $response->toArray(true);
        });
    }

    public function getPartColors(string $partNumber): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.getPartColors.' . $partNumber, function() use ($partNumber): array {
            $response = $this->rebrickableClient->request(
                'GET',
                '/api/v3/lego/parts/' . $partNumber . '/colors',
            );

            return $response->toArray(true);
        });
    }

    public function getPartSets(string $partNumber, int $color): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.getPartSets.' . $partNumber . '.' . $color, function() use ($partNumber, $color): array {
            $response = $this->rebrickableClient->request(
                'GET',
                '/api/v3/lego/parts/' . $partNumber . '/colors/' . $color . '/sets/',
            );

            return $response->toArray(true);
        });
    }

    public function getPartDetails(string $url): string
    {
        return $this->arrayRedisCache->get('rebrickable_client.getPartDetails.' . $url, function() use ($url): string {
            $response = $this->rebrickableClient->request(
                'GET',
                $url,
            );

            return $response->getContent(true);
        });
    }

    public function getColors(): array
    {
        return $this->arrayRedisCache->get('rebrickable_client.getColor', function(): array {
            $response = $this->rebrickableClient->request(
                'GET',
                '/api/v3/lego/colors/', [
                    'query' => [
                        'page_size' => 1000,
                    ]
                ]
            );

            return $response->toArray();
        });
    }

    private function getUserToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $response = $this->rebrickableClient->request(
            'POST',
            '/api/v3/users/_token/', [
                'headers' => [
                    'accept' => 'application/json',
                ],
                'body' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ]
            ]
        );

        $this->token = $response->toArray(true)['user_token'];

        return $this->token;
    }

    public function getPartsOfPartList(int $partListId): array
    {
        $response = $this->rebrickableClient->request(
            'GET',
            '/api/v3/users/' . $this->getUserToken() . '/partlists/' . $partListId . '/parts/', [
                'query' => [
                    'page_size' => 1000,
                ],
            ],
        );

        return $response->toArray(true);
    }
}
