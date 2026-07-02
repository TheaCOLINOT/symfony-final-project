<?php

namespace App\Controller;

use App\Repository\CatRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CAT')]
final class CatController extends AbstractController
{
    #[Route('/espace-cat', name: 'app_cat')]
    public function index(
        ServiceRepository $serviceRepository,
        CatRepository $catRepository,
    ): Response {
        return $this->render('cat/index.html.twig', [
            'user' => $this->getUser(),
            'available_services' => $serviceRepository->findGlobalServices(),
            'cats' => $catRepository->findAllOrderedBySpeciality(),
        ]);
    }
}
