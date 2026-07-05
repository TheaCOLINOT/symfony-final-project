<?php

namespace App\Controller;

use App\Entity\Cat;
use App\Entity\Location;
use App\Entity\Service;
use App\Entity\User;
use App\Event\ReservationConfirmedEvent;
use App\Form\ReservationBookType;
use App\Repository\LocationRepository;
use App\Service\ReservationFactoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Contrôleur du parcours de réservation côté client (connecté)
#[IsGranted('ROLE_USER')]
final class ReservationController extends AbstractController
{
    // Étape 1 : le client choisit la date et l'heure du rendez-vous
    #[Route(
        '/reservation/reserver/{serviceId}/{locationId}/{catId}',
        name: 'app_reservation_book',
        methods: ['GET', 'POST'],
    )]
    public function book(
        #[MapEntity(id: 'serviceId')] Service $service,
        #[MapEntity(id: 'locationId')] Location $location,
        #[MapEntity(id: 'catId')] Cat $cat,
        Request $request,
        ReservationFactoryService $reservationFactory,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // On vérifie que l'offre existe toujours (salon + masseur + prestation)
        $reservationFactory->assertOfferAvailable($service, $location, $cat);

        $form = $this->createForm(ReservationBookType::class);
        $form->handleRequest($request);

        // Si le formulaire est valide, on affiche la page de confirmation (sans enregistrer encore)
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \DateTimeInterface $reservationAt */
            $reservationAt = $form->get('reservationAt')->getData();

            return $this->render('reservation/confirm.html.twig', [
                'service' => $service,
                'location' => $location,
                'cat' => $cat,
                'reservationAt' => $reservationAt,
            ]);
        }

        return $this->render('reservation/book.html.twig', [
            'service' => $service,
            'location' => $location,
            'cat' => $cat,
            'form' => $form->createView(),
        ]);
    }

    // Étape 2 : validation définitive après la page de récapitulatif
    #[Route('/reservation/valider', name: 'app_reservation_confirm', methods: ['POST'])]
    public function confirm(
        Request $request,
        EntityManagerInterface $entityManager,
        ReservationFactoryService $reservationFactory,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Protection CSRF pour éviter les fausses soumissions
        if (!$this->isCsrfTokenValid('reservation_confirm', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Données cachées envoyées depuis le formulaire de confirmation
        $serviceId = $request->request->getInt('service_id');
        $locationId = $request->request->getInt('location_id');
        $catId = $request->request->getInt('cat_id');
        $reservationAtRaw = (string) $request->request->get('reservation_at');

        $service = $entityManager->find(Service::class, $serviceId);
        $location = $entityManager->find(Location::class, $locationId);
        $cat = $entityManager->find(Cat::class, $catId);

        if (!$service instanceof Service || !$location instanceof Location || !$cat instanceof Cat) {
            $this->addFlash('error', 'Cette prestation n\'est plus disponible.');

            return $this->redirectToRoute('app_search');
        }

        // Deux formats possibles selon le navigateur
        $reservationAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $reservationAtRaw)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $reservationAtRaw);

        // Le créneau doit être dans le futur
        if (!$reservationAt instanceof \DateTimeImmutable || $reservationAt <= new \DateTimeImmutable('now')) {
            $this->addFlash('error', 'La date choisie n\'est plus valide.');

            return $this->redirectToRoute('app_reservation_book', [
                'serviceId' => $serviceId,
                'locationId' => $locationId,
                'catId' => $catId,
            ]);
        }

        // Création et enregistrement de la réservation en base
        $reservation = $reservationFactory->create($user, $service, $location, $cat, $reservationAt);
        $entityManager->persist($reservation);
        $entityManager->flush();

        $eventDispatcher->dispatch(new ReservationConfirmedEvent($reservation));

        $this->addFlash('success', sprintf(
            'Votre réservation est confirmée pour le %s à %s.',
            $reservationAt->format('d/m/Y'),
            $reservationAt->format('H:i')
        ));

        return $this->redirectToRoute('app_user_reservations');
    }

    // Page de confirmation avant de lancer le live chat (pas de créneau horaire)
    #[Route(
        '/reservation/live-chat/{serviceId}/{catId}',
        name: 'app_reservation_book_remote',
        methods: ['GET'],
    )]
    public function bookRemote(
        #[MapEntity(id: 'serviceId')] Service $service,
        #[MapEntity(id: 'catId')] Cat $cat,
        ReservationFactoryService $reservationFactory,
        LocationRepository $locationRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $location = $locationRepository->findRemoteLocation();
        if ($location === null) {
            throw $this->createNotFoundException('Le live chat à distance n\'est pas disponible.');
        }

        // Vérifie que c'est bien la bonne prestation + le bon chat
        $reservationFactory->assertRemoteOfferAvailable($service, $location, $cat);

        return $this->render('reservation/book_remote.html.twig', [
            'service' => $service,
            'location' => $location,
            'cat' => $cat,
        ]);
    }

    // Enregistre la réservation live chat puis redirige vers la page de chat
    #[Route('/reservation/live-chat/valider', name: 'app_reservation_confirm_remote', methods: ['POST'])]
    public function confirmRemote(
        Request $request,
        EntityManagerInterface $entityManager,
        ReservationFactoryService $reservationFactory,
        LocationRepository $locationRepository,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('reservation_confirm_remote', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // IDs envoyés par le formulaire caché
        $serviceId = $request->request->getInt('service_id');
        $catId = $request->request->getInt('cat_id');

        $service = $entityManager->find(Service::class, $serviceId);
        $cat = $entityManager->find(Cat::class, $catId);
        $location = $locationRepository->findRemoteLocation();

        if (!$service instanceof Service || !$cat instanceof Cat || $location === null) {
            $this->addFlash('error', 'Cette prestation n\'est plus disponible.');

            return $this->redirectToRoute('app_search');
        }

        // Pas de date future : le live chat démarre tout de suite
        $reservationAt = new \DateTimeImmutable('now');
        $reservation = $reservationFactory->create($user, $service, $location, $cat, $reservationAt);
        $entityManager->persist($reservation);
        $entityManager->flush();

        $eventDispatcher->dispatch(new ReservationConfirmedEvent($reservation));

        $this->addFlash('success', 'Votre live chat avec le masseur chat est prêt. Miaou !');

        // On envoie directement l'utilisateur sur la conversation
        return $this->redirectToRoute('app_live_chat', [
            'reservationId' => $reservation->getId(),
        ]);
    }
}
