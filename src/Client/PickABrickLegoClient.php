<?php
declare(strict_types=1);

namespace App\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PickABrickLegoClient
{
    public function __construct(
        private HttpClientInterface $pickABrickLegoClient,
    )
    {
    }


    public function getPickABrickBricks(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../../public/uploads/pickABrickPrices.json'), true);
    }
}
