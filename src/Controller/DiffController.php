<?php
declare(strict_types=1);

namespace App\Controller;

use App\Client\RebrickableClient;
use App\Dto\ItemDto;
use App\Form\Type\SearchType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class DiffController extends AbstractController
{
    #[Route('/diff', name: 'diff')]
    public function diffAction(Request $request, RebrickableClient $rebrickableClient): Response
    {
        $colors = [];
        foreach ($rebrickableClient->getColors()['results'] as $color) {
            foreach ($color['external_ids']['BrickLink']['ext_ids'] ?? [] as $id) {
                $colors[$id] = $color;
            }
        }

        $needed = $this->getItems('needed.xml');
        $incoming = $this->getItems('incoming.xml');

        $neededPartNumbers = array_unique(array_map(fn (array $item): string => $item['itemId'], $needed));

        $neededParts = $rebrickableClient->getParts($neededPartNumbers);

        foreach ($neededParts['results'] as $part) {
            foreach ($needed as &$need) {
                if ($need['itemId'] === $part['part_num']) {
                    $need['part'] = $part;
                }
            }
        }

        foreach ($needed as &$need) {
            if (!isset($need['part'])) {
                foreach ($rebrickableClient->getPartsByBricklinkId($need['itemId'])['results'] as $part) {
                    $need['part'] = $part;
                }
            }
        }

        dump($colors);

        $overlapping = [];
        foreach ($needed as $item) {
            foreach ($incoming as $income) {
                if ($this->isSameId($income['itemId'], $item) && $income['color'] === $item['color']) {

                    $itemColor = null;
                    if (isset($item['part']) && isset($colors[$item['color']])) {
                        $partColors = $rebrickableClient->getPartColors($item['part']['part_num']);

                        foreach ($partColors['results'] as $partColor) {
                            if ($partColor['color_id'] == $colors[$item['color']]['id'] ?? 0) {
                                $itemColor = $partColor;
                            }
                        }
                    }

                    $overlapping[] = [
                        'itemId' => $income['itemId'],
                        'neededItemId' => $item['itemId'],
                        'color' => $income['color'],
                        'neededQty' => $item['qty'],
                        'incomingQty' => $income['qty'],
                        'price' => $item['price'],
                        'part' => $item['part'] ?? null,
                        'colorDetails' => $itemColor,
                    ];
                }
            }
        }

        usort($overlapping, function(array $a, array $b) use ($colors) {
            return $a['part']['name'] <=> $b['part']['name'] ?: $colors[$a['color']]['name'] <=> $colors[$b['color']]['name'];
        });

        return $this->render('diff.html.twig', [
            'needed' => $needed,
            'incoming' => $incoming,
            'overlapping' => $overlapping,
            'colors' => $colors,
        ]);
    }

    private function getItems(string $filename): array
    {
        $inventory = new Crawler(file_get_contents('/vagrant/rebrickable-helper/public/uploads/' . $filename));

        $items = $inventory->filter('ITEM')->each(function (Crawler $item) {
            $price = 0.0;

            try {
                $price = (float) $item->filter('MAXPRICE')->text();
            } catch (Exception) { }

            return [
                'itemId' => $item->filter('ITEMID')->text(),
                'color' => $item->filter('COLOR')->text(),
                'qty' => $item->filter('MINQTY')->text(),
                'price' => $price,
            ];
        });

        return $items;
    }

    private function isSameId(string $id, array $item): bool
    {
        if ($item['itemId'] === $id) {
            return true;
        }

        foreach ($item['part']['molds'] ?? [] as $moldId) {
            if ($moldId === $id) {
                return true;
            }
        }

        return false;
    }
}
