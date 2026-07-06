<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Empêche la connexion tant que l'e-mail n'a pas été confirmé.
 */
final class UserEmailVerifiedChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isEmailVerified()) {
            return;
        }

        throw new CustomUserMessageAccountStatusException(
            'Veuillez confirmer votre adresse e-mail avant de vous connecter. Consultez votre boîte mail (Mailpit en développement : http://localhost:8026).',
        );
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
