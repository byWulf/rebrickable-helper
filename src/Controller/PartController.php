<?php
declare(strict_types=1);

namespace App\Controller;

use App\Client\RebrickableClient;
use App\Form\Type\SearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class PartController extends AbstractController
{
    #[Route('/part/{partNumber}', name: 'part')]
    public function searchAction(string $partNumber, RebrickableClient $rebrickableClient, ): Response
    {
        return $this->render('part.html.twig', [
            'part' => $rebrickableClient->getPart($partNumber),
            'colors' => $rebrickableClient->getPartColors($partNumber),
        ]);
    }
}
