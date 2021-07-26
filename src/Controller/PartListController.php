<?php
declare(strict_types=1);

namespace App\Controller;

use App\Client\BricksAndPiecesLegoClient;
use App\Client\PickABrickLegoClient;
use App\Client\RebrickableClient;
use App\Dto\ItemDto;
use App\Form\Type\SearchType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class PartListController extends AbstractController
{
    #[Route('/partlist/{partListId}', name: 'partlist')]
    public function partListAction(RebrickableClient $rebrickableClient, BricksAndPiecesLegoClient $bricksAndPiecesLegoClient, int $partListId, PickABrickLegoClient $pickABrickLegoClient): Response
    {
        $pickABrickBricks = $pickABrickLegoClient->getPickABrickBricks();
        dump($pickABrickBricks[0]);

        $colors = $rebrickableClient->getColors()['results'];

        $parts = [];
        $setsVisited = [];

        $partsFromList = $rebrickableClient->getPartsOfPartList($partListId)['results'];
        usort($partsFromList, fn($a, $b) => ($a['part']['part_num']) <=> ($b['part']['part_num']) ?: $a['color']['name'] <=> $b['color']['name']);

        foreach ($partsFromList as $part) {
            if ($part['part']['part_num'] !== '3622' || $part['color']['name'] !== 'Tan') {
                //continue;
            }

            $sets = $originalSets = $rebrickableClient->getPartSets($part['part']['part_num'], $part['color']['id'])['results'];
            usort($sets, fn ($a, $b) => $b['year'] <=> $a['year']);
            $part['sets'] = $sets;

            //dump($sets);
            //dump($part);

            $legoIds = [$part['part']['part_num']];
            if (isset($part['part']['external_ids']['LEGO'])) {
                $legoIds = array_merge($legoIds, $part['part']['external_ids']['LEGO']);
            }

            $colorNames = null;
            foreach ($colors as $colorRow) {
                if ($colorRow['id'] === $part['color']['id']) {
                    $colorNames = array_map('strtolower',array_merge(...$colorRow['external_ids']['LEGO']['ext_descrs']));
                    break;
                }
            }


            foreach ($legoIds as $legoId) {
                //dump([$legoId, $colorNames]);
                if ($legoId !== null && $colorNames !== null) {
                    foreach ($pickABrickBricks as $brick) {
                        if ((string) $brick['variant']['attributes']['designNumber'] === $legoId && in_array(strtolower($brick['variant']['attributes']['colour']), $colorNames, true)) {
                            $part['pabPrice'] = $brick['variant']['price']['centAmount'] / 100;
                            $part['pabIsAvailable'] = $brick['variant']['attributes']['canAddToBag'];
                            break;
                        }
                    }


                    foreach ($sets as $set) {
                        if (in_array($set['set_num'], $setsVisited, true)) {
                            $sets = [$set];
                            break;
                        }
                    }

                    for ($i = 0; $i < min(5, count($sets)); $i++) {
                        $setNum = $sets[$i]['set_num'];
                        $setNum = preg_replace('/-\d+$/', '', $setNum);

                        if (!preg_match('/^\d+$/', $setNum)) {
                            continue;
                        }

                        $legoParts = $bricksAndPiecesLegoClient->getPartsBySet($setNum)['bricks'] ?? [];
                        $setsVisited[] = $sets[$i]['set_num'];
                        foreach ($legoParts as $brick) {
                            if ((string) $brick['designId'] === $legoId) {
                                //dump($brick);
                            }
                        }

                        foreach ($legoParts as $brick) {
                            if ((string) $brick['designId'] === $legoId && in_array(strtolower($brick['colorFamily']), $colorNames, true)) {
                                $part['price'] = $brick['price']['amount'];
                                $part['isAvailable'] = $brick['isAvailable'] && !$brick['isSoldOut'];
                                $part['set'] = $setNum;
                                break 3;
                            }
                        }
                    }
                }
            }

            if (!isset($part['price'])) {
                $partColors = $rebrickableClient->getPartColors($part['part']['part_num'])['results'];
                $elementIds = null;
                foreach ($partColors as $partColor) {
                    if ($partColor['color_id'] === $part['color']['id']) {
                        $elementIds = $partColor['elements'];
                    }
                }

                if ($elementIds !== null) {
                    for ($i = 0; $i < min(5, count($originalSets)); $i++) {
                        $setNum = $originalSets[$i]['set_num'];
                        $setNum = preg_replace('/-\d+$/', '', $setNum);

                        if (!preg_match('/^\d+$/', $setNum)) {
                            continue;
                        }

                        $legoParts = $bricksAndPiecesLegoClient->getPartsBySet($setNum)['bricks'] ?? [];

                        foreach ($legoParts as $brick) {
                            if (in_array($brick['itemNumber'], $elementIds)) {
                                $part['price'] = $brick['price']['amount'];
                                $part['isAvailable'] = $brick['isAvailable'];
                                $part['set'] = $setNum;
                                break 2;
                            }
                        }
                    }
                }
            }

            $parts[] = $part;
        }


        return $this->render('part_list.html.twig', [
            'parts' => $parts,
        ]);
    }

}
