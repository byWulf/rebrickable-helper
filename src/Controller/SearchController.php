<?php
declare(strict_types=1);

namespace App\Controller;

use App\Client\RebrickableClient;
use App\Form\Type\SearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search')]
    public function searchAction(Request $request, RebrickableClient $rebrickableClient, ): Response
    {
        $results = null;

        $searchForm = $this->createForm(SearchType::class);

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            try {
                $results = $rebrickableClient->searchForParts($searchForm->getData()->getQuery());
            } catch (ExceptionInterface $e) {
                $this->addFlash('danger', 'Search failed: ' . $e->getMessage());
            }
        }

        return $this->render('search.html.twig', [
            'searchForm' => $searchForm->createView(),
            'results' => $results,
        ]);
    }
}
