<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    // Page d'accueil du site (accessible à tous)
    #[Route('/', name: 'app_home')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $upcomingReservations = [];

        // Les clients simples voient leurs prochaines réservations sur l'accueil
        if ($user instanceof User && $this->isStandardUser()) {
            $upcomingReservations = $reservationRepository->findUpcomingByUser($user, 3);
        }

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'upcoming_reservations' => $upcomingReservations,
        ]);
    }

    // Vérifie que l'utilisateur est un client (pas manager ni masseur chat)
    private function isStandardUser(): bool
    {
        return $this->isGranted('ROLE_USER')
            && !$this->isGranted('ROLE_MANAGER')
            && !$this->isGranted('ROLE_CAT');
    }
}
