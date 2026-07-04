<?php
namespace App\Controller;
use App\Entity\Cat;
use App\Entity\Service;
use App\Entity\User;
use App\Form\CatServiceSelectionType;
use App\Form\CatType;
use App\Repository\CatRepository;
use App\Repository\ServiceRepository;
use App\Service\ReservationPlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
// Espace réservé aux masseurs chat (ROLE_CAT)
#[IsGranted('ROLE_CAT')]
final class CatController extends AbstractController
{
    // Tableau de bord : profil, services, selon l'état du compte
    #[Route('/espace-cat', name: 'app_cat')]
    public function index(
        CatRepository $catRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $cat = $catRepository->findOneByUser($user);
        $profileForm = null;
        $serviceForm = null;
        // Pas encore de profil → formulaire de création
        if ($cat === null) {
            $profileForm = $this->createForm(CatType::class, new Cat(), [
                'action' => $this->generateUrl('app_cat_profile'),
                'method' => 'POST',
            ])->createView();
        // Profil existant et rattaché à un salon → formulaire de choix des services
        } elseif ($cat->hasLocations()) {
            $serviceForm = $this->createForm(CatServiceSelectionType::class, [
                'services' => $cat->getServices()->toArray(),
            ], [
                'action' => $this->generateUrl('app_cat_services'),
                'method' => 'POST',
                'services' => $serviceRepository->findGlobalServices(),
            ])->createView();
        }
        return $this->render('cat/index.html.twig', [
            'user' => $user,
            'cat' => $cat,
            'profile_form' => $profileForm,
            'available_services' => $serviceRepository->findGlobalServices(),
            'service_form' => $serviceForm,
        ]);
    }
    // Crée le profil masseur chat (une seule fois par utilisateur)
    #[Route('/espace-cat/profil', name: 'app_cat_profile', methods: ['POST'])]
    public function createProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        CatRepository $catRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        // On évite les doublons de profil
        if ($catRepository->findOneByUser($user) !== null) {
            $this->addFlash('error', 'Votre profil chat existe déjà.');
            return $this->redirectToRoute('app_cat');
        }
        $cat = new Cat();
        $cat->setUser($user);
        $form = $this->createForm(CatType::class, $cat);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de créer votre profil chat.');
            return $this->redirectToRoute('app_cat');
        }
        $entityManager->persist($cat);
        $entityManager->flush();
        $this->addFlash('success', 'Votre profil masseur chat a été créé. Un manager de salon pourra vous recruter.');
        return $this->redirectToRoute('app_cat');
    }
    // Enregistre les services que le masseur chat propose
    #[Route('/espace-cat/services', name: 'app_cat_services', methods: ['POST'])]
    public function updateServices(
        Request $request,
        EntityManagerInterface $entityManager,
        CatRepository $catRepository,
        ServiceRepository $serviceRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $cat = $catRepository->findOneByUser($user);
        // Il faut être recruté dans un salon avant de choisir ses prestations
        if ($cat === null || !$cat->hasLocations()) {
            $this->addFlash('error', 'Vous devez être rattaché à au moins un salon avant de choisir vos services.');
            return $this->redirectToRoute('app_cat');
        }
        $form = $this->createForm(CatServiceSelectionType::class, [
            'services' => $cat->getServices()->toArray(),
        ], [
            'services' => $serviceRepository->findGlobalServices(),
        ]);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de mettre à jour vos services.');
            return $this->redirectToRoute('app_cat');
        }
        // On vide d'abord la liste actuelle puis on remet la sélection du formulaire
        foreach ($cat->getServices()->toArray() as $service) {
            $cat->removeService($service);
        }
        /** @var list<Service> $selectedServices */
        $selectedServices = $form->get('services')->getData();
        foreach ($selectedServices as $service) {
            $cat->addService($service);
        }
        $entityManager->flush();
        $this->addFlash('success', 'Vos services proposés ont été mis à jour.');
        return $this->redirectToRoute('app_cat');
    }
    // Affiche le planning des réservations (vue semaine ou mois)
    #[Route('/espace-cat/planning', name: 'app_cat_planning', methods: ['GET'])]
    public function planning(
        Request $request,
        CatRepository $catRepository,
        ReservationPlanningService $planningService,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $cat = $catRepository->findOneByUser($user);
        if ($cat === null) {
            $this->addFlash('error', 'Créez d\'abord votre profil masseur chat.');
            return $this->redirectToRoute('app_cat');
        }
        // Paramètre ?view=week ou ?view=month (défaut : semaine)
        $view = $request->query->getString('view', 'week');
        if (!in_array($view, ['week', 'month'], true)) {
            $view = 'week';
        }
        // Date de référence pour naviguer dans le calendrier (?date=...)
        $dateParam = $request->query->get('date');
        $dateParam = is_string($dateParam) ? $dateParam : null;
        if ($view === 'month') {
            $period = $planningService->resolveMonthPeriod($dateParam);
            $monthGrid = $planningService->buildMonthGrid($cat, $period['start'], $period['end']);
            $weekDays = [];
            $prevDate = $period['reference']->modify('-1 month')->format('Y-m-d');
            $nextDate = $period['reference']->modify('+1 month')->format('Y-m-d');
            $title = $planningService->formatMonthTitle($period['reference']);
        } else {
            $period = $planningService->resolveWeekPeriod($dateParam);
            $weekDays = $planningService->buildWeekDays($cat, $period['start'], $period['end']);
            $monthGrid = [];
            $prevDate = $period['start']->modify('-7 days')->format('Y-m-d');
            $nextDate = $period['start']->modify('+7 days')->format('Y-m-d');
            $title = sprintf('Semaine du %s', $period['start']->format('d/m/Y'));
        }
        return $this->render('cat/planning.html.twig', [
            'cat' => $cat,
            'view' => $view,
            'title' => $title,
            'current_date' => $period['reference']->format('Y-m-d'),
            'prev_date' => $prevDate,
            'next_date' => $nextDate,
            'week_days' => $weekDays,
            'month_grid' => $monthGrid,
        ]);
    }
}
