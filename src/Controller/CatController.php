<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CAT')]
final class CatController extends AbstractController
{
    #[Route('/espace-cat', name: 'app_cat')]
    public function index(): Response
    {
        return $this->render('cat/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
