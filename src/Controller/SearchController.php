<?php

namespace App\Controller;

use App\Form\PrestationSearchType;
use App\Service\PrestationSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Recherche de prestations (clients connectés uniquement)
#[IsGranted('ROLE_USER')]
final class SearchController extends AbstractController
{
    // Page de recherche avec filtres ville, service et mot-clé
    #[Route('/recherche', name: 'app_search', methods: ['GET'])]
    public function index(
        Request $request,
        PrestationSearchService $prestationSearchService,
    ): Response {
        $form = $this->createForm(PrestationSearchType::class);
        $form->handleRequest($request);

        // Récupération des critères depuis le formulaire GET
        $location = $form->get('location')->getData();
        $service = $form->get('service')->getData();
        $query = $form->get('q')->getData();
        $hasFilters = $location !== null || $service !== null || (is_string($query) && trim($query) !== '');

        // Le service renvoie les offres disponibles selon les filtres
        $offers = $prestationSearchService->search(
            $location,
            $service,
            is_string($query) ? $query : null,
        );

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'offers' => $offers,
            'has_filters' => $hasFilters,
        ]);
    }
}
