<?php
namespace App\Controller;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setName($request->request->get('name'));
            $user->setFirstname($request->request->get('firstname'));
            $user->setPhone($request->request->get('phone'));
            $user->setUserRole(UserRole::User);
            // Encodage sécurisé du mot de passe (jamais stocké en clair)
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $request->request->get('password')
            );
            $user->setPassword($hashedPassword);
            // Persistance en base de données
            $entityManager->persist($user);
            $entityManager->flush();
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
