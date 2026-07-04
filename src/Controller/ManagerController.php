<?php
namespace App\Controller;
use App\Entity\Service;
use App\Entity\User;
use App\Form\CatAssignType;
use App\Form\ServiceType;
use App\Repository\CatRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use App\Service\ManagerContextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
// Espace réservé aux managers de salon (ROLE_MANAGER)
#[IsGranted('ROLE_MANAGER')]
final class ManagerController extends AbstractController
{
    // Tableau de bord manager : salon, services, masseurs chat
    #[Route('/manager', name: 'app_manager')]
    public function index(
        ManagerContextService $managerContext,
        ServiceRepository $serviceRepository,
        CatRepository $catRepository,
        UserRepository $userRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $managedLocation = $managerContext->getManagedLocation($user);
        $isGlobalAdmin = $managerContext->isGlobalAdmin($user);
        // Manager sans salon assigné → message d'attente
        if ($managedLocation === null) {
            return $this->render('manager/index.html.twig', [
                'user' => $user,
                'managed_location' => null,
                'is_global_admin' => false,
                'no_location' => true,
            ]);
        }
        // Formulaire pour créer un service global (catalogue commun)
        $service = new Service();
        $service->setIsGlobal(true);
        $serviceForm = $this->createForm(ServiceType::class, $service, [
            'action' => $this->generateUrl('app_manager_service_create'),
        ]);
        // Les managers globaux ne gèrent pas de masseurs chat localement
        $availableMasseurs = $isGlobalAdmin ? [] : $userRepository->findCatMasseursNotInLocation($managedLocation);
        $addCatForm = null;
        if (!$isGlobalAdmin) {
            $addCatForm = $this->createForm(CatAssignType::class, null, [
                'action' => $this->generateUrl('app_manager_cat_add'),
                'masseurs' => $availableMasseurs,
            ])->createView();
        }
        $cats = $isGlobalAdmin ? [] : $catRepository->findByLocation($managedLocation);
        return $this->render('manager/index.html.twig', [
            'user' => $user,
            'managed_location' => $managedLocation,
            'is_global_admin' => $isGlobalAdmin,
            'no_location' => false,
            'service_form' => $serviceForm->createView(),
            'add_cat_form' => $addCatForm,
            'available_masseurs_count' => count($availableMasseurs),
            'services' => $serviceRepository->findGlobalServices(),
            'cats' => $cats,
        ]);
    }
    // Enregistre un nouveau service dans le catalogue global
    #[Route('/manager/services/create', name: 'app_manager_service_create', methods: ['POST'])]
    public function createService(
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContext,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        // Seuls les managers avec un salon peuvent créer des services
        if ($managerContext->getManagedLocation($user) === null) {
            throw $this->createAccessDeniedException();
        }
        $service = new Service();
        $service->setIsGlobal(true);
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de créer ce service.');
            return $this->redirectToRoute('app_manager');
        }
        $entityManager->persist($service);
        $entityManager->flush();
        $this->addFlash('success', 'Le service global a bien été créé.');
        return $this->redirectToRoute('app_manager');
    }
    // Recrute un masseur chat dans le salon du manager
    #[Route('/manager/cats/add', name: 'app_manager_cat_add', methods: ['POST'])]
    public function addCat(
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContext,
        CatRepository $catRepository,
        UserRepository $userRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $managedLocation = $managerContext->getManagedLocation($user);
        // Les managers globaux ne peuvent pas ajouter de masseurs à un salon local
        if ($managedLocation === null || $managedLocation->isGlobal()) {
            throw $this->createAccessDeniedException();
        }
        $availableMasseurs = $userRepository->findCatMasseursNotInLocation($managedLocation);
        $form = $this->createForm(CatAssignType::class, null, [
            'masseurs' => $availableMasseurs,
        ]);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible d\'ajouter ce masseur chat au salon.');
            return $this->redirectToRoute('app_manager');
        }
        /** @var User|null $masseurUser */
        $masseurUser = $form->get('masseurUser')->getData();
        if (!$masseurUser instanceof User) {
            $this->addFlash('error', 'Veuillez sélectionner un masseur chat inscrit.');
            return $this->redirectToRoute('app_manager');
        }
        $cat = $catRepository->findOneByUser($masseurUser);
        // Le masseur doit avoir créé son profil dans son espace perso
        if ($cat === null) {
            $this->addFlash(
                'error',
                'Ce masseur doit d\'abord compléter son profil dans son espace personnel avant d\'être ajouté à un salon.'
            );
            return $this->redirectToRoute('app_manager');
        }
        if ($cat->isInLocation($managedLocation)) {
            $this->addFlash('error', 'Ce masseur chat est déjà présent dans votre salon.');
            return $this->redirectToRoute('app_manager');
        }
        $cat->addLocation($managedLocation);
        $entityManager->flush();
        $this->addFlash('success', 'Le masseur chat a bien été ajouté à votre salon.');
        return $this->redirectToRoute('app_manager');
    }
}
