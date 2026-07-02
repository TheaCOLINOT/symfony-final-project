<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Manager;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\AdminLocationType;
use App\Form\ChangeUserRoleType;
use App\Repository\LocationRepository;
use App\Repository\ManagerRepository;
use App\Repository\UserRepository;
use App\Security\Voter\GlobalAdminVoter;
use App\Service\ManagerContextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(GlobalAdminVoter::GLOBAL_ADMIN)]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAllOrderedByName();
        $forms = [];

        foreach ($users as $user) {
            $forms[$user->getId()] = $this->createForm(ChangeUserRoleType::class, [
                'role' => $user->getUserRole() ?? UserRole::User,
            ], [
                'action' => $this->generateUrl('app_admin_user_role', ['id' => $user->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'forms' => $forms,
        ]);
    }

    #[Route('/admin/locations', name: 'app_admin_locations')]
    public function locations(
        Request $request,
        EntityManagerInterface $entityManager,
        LocationRepository $locationRepository,
        ManagerRepository $managerRepository,
    ): Response {
        $location = new Location();
        $form = $this->createForm(AdminLocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $managerUser */
            $managerUser = $form->get('managerUser')->getData();

            $manager = $managerRepository->findOneByUser($managerUser);
            if ($manager === null) {
                $manager = new Manager();
                $manager->setUser($managerUser);
                $manager->setIsAdmin(false);
                $entityManager->persist($manager);
            }

            $location->setIsGlobal(false);
            $manager->setLocation($location);
            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le salon de %s a été créé avec %s %s comme manager.',
                $location->getCity(),
                $managerUser->getFirstname(),
                $managerUser->getName()
            ));

            return $this->redirectToRoute('app_admin_locations');
        }

        return $this->render('admin/locations.html.twig', [
            'form' => $form->createView(),
            'locations' => $locationRepository->findAllOrderedByCity(),
            'globalLocation' => $locationRepository->findGlobalLocation(),
            'availableGlobalManagers' => $userRepository->findManagersWithoutLocation(),
        ]);
    }

    #[Route('/admin/global-managers/add', name: 'app_admin_global_manager_add', methods: ['POST'])]
    public function addGlobalManager(
        Request $request,
        EntityManagerInterface $entityManager,
        LocationRepository $locationRepository,
        ManagerRepository $managerRepository,
        UserRepository $userRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('add_global_manager', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $userId = $request->request->getInt('manager_user_id');
        $managerUser = $userRepository->find($userId);

        if (!$managerUser instanceof User || !$managerUser->hasRole(UserRole::Manager)) {
            $this->addFlash('error', 'Veuillez sélectionner un utilisateur manager valide.');

            return $this->redirectToRoute('app_admin_locations');
        }

        $globalLocation = $locationRepository->findGlobalLocation();
        if ($globalLocation === null) {
            $this->addFlash('error', 'La localisation globale n\'existe pas encore.');

            return $this->redirectToRoute('app_admin_locations');
        }

        $manager = $managerRepository->findOneByUser($managerUser);
        if ($manager === null) {
            $manager = new Manager();
            $manager->setUser($managerUser);
            $entityManager->persist($manager);
        }

        if ($manager->getLocation() !== null) {
            $this->addFlash('error', 'Ce manager est déjà assigné à une localisation.');

            return $this->redirectToRoute('app_admin_locations');
        }

        $manager->setIsAdmin(true);
        $manager->setLocation($globalLocation);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            '%s %s a été ajouté comme manager global.',
            $managerUser->getFirstname(),
            $managerUser->getName()
        ));

        return $this->redirectToRoute('app_admin_locations');
    }

    #[Route('/admin/users/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function updateUserRole(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        ManagerContextService $managerContextService,
    ): Response {
        $form = $this->createForm(ChangeUserRoleType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Impossible de modifier le rôle de cet utilisateur.');

            return $this->redirectToRoute('app_admin_users');
        }

        /** @var UserRole $newRole */
        $newRole = $form->get('role')->getData();
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            $managerContextService->isGlobalAdmin($user)
            && $newRole !== UserRole::Manager
            && $user->getId() === $currentUser->getId()
        ) {
            $this->addFlash('error', 'Vous ne pouvez pas retirer votre propre accès manager global.');

            return $this->redirectToRoute('app_admin_users');
        }

        $user->setUserRole($newRole);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Le rôle de %s %s a été mis à jour.',
            $user->getFirstname(),
            $user->getName()
        ));

        return $this->redirectToRoute('app_admin_users');
    }
}
