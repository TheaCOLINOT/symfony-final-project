<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Event\UserRegisteredEvent;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

// Authentification : connexion, inscription et déconnexion
class SecurityController extends AbstractController
{
    // Affiche le formulaire de connexion (Symfony gère l'authentification en POST)
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // En cas d'erreur de connexion (mauvais mot de passe, etc.)
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier email saisi par l'internaute (pour pré-remplir le champ)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    // Inscription d'un nouvel utilisateur (rôle client par défaut)
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide, veuillez réessayer.');

                return $this->redirectToRoute('app_register');
            }

            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');
            $firstname = trim((string) $request->request->get('firstname', ''));
            $name = trim((string) $request->request->get('name', ''));
            $phone = trim((string) $request->request->get('phone', ''));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Veuillez saisir une adresse e-mail valide.');

                return $this->redirectToRoute('app_register');
            }

            if (strlen($password) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');

                return $this->redirectToRoute('app_register');
            }

            if ($firstname === '' || $name === '') {
                $this->addFlash('error', 'Le prénom et le nom sont obligatoires.');

                return $this->redirectToRoute('app_register');
            }

            if ($userRepository->findOneByEmail($email) instanceof User) {
                $this->addFlash('error', 'Un compte existe déjà avec cette adresse e-mail.');

                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setFirstname($firstname);
            $user->setPhone($phone !== '' ? $phone : null);
            $user->setUserRole(UserRole::User);
            $user->setPassword($passwordHasher->hashPassword($user, $password));

            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $eventDispatcher->dispatch(new UserRegisteredEvent($user));
            } catch (\Throwable) {
                // L'inscription reste valide même si l'e-mail de bienvenue échoue.
            }

            $this->addFlash('success', 'Votre compte a été créé. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig');
    }

    // Déconnexion : Symfony intercepte cette route automatiquement via security.yaml
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide, Symfony intercepte la route automatiquement.');
    }
}
