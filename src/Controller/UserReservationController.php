<?php



namespace App\Controller;



use App\Entity\User;

use App\Repository\ReservationRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;



// Liste des réservations personnelles du client connecté

#[IsGranted('ROLE_USER')]

final class UserReservationController extends AbstractController

{

    // Affiche les réservations à venir et passées de l'utilisateur

    #[Route('/mes-reservations', name: 'app_user_reservations')]

    public function index(ReservationRepository $reservationRepository): Response

    {

        $user = $this->getUser();

        if (!$user instanceof User) {

            throw $this->createAccessDeniedException();

        }



        return $this->render('reservation/user_list.html.twig', [

            'upcoming_reservations' => $reservationRepository->findUpcomingByUser($user),

            'past_reservations' => $reservationRepository->findPastByUser($user),

        ]);

    }

}

