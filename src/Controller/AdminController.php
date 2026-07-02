<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Form\ChangeUserRoleType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
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

    #[Route('/admin/users/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
    public function updateUserRole(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
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
            $user->getId() === $currentUser->getId()
            && $newRole !== UserRole::Admin
        ) {
            $this->addFlash('error', 'Vous ne pouvez pas retirer votre propre rôle administrateur.');

            return $this->redirectToRoute('app_admin_users');
        }

        if (
            $user->hasRole(UserRole::Admin)
            && $newRole !== UserRole::Admin
            && $userRepository->countByRole(UserRole::Admin) <= 1
        ) {
            $this->addFlash('error', 'Impossible de retirer le rôle du dernier administrateur.');

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
