<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Génère et valide les jetons de confirmation d'inscription par e-mail.
 */
final class EmailVerificationTokenService
{
    private const TOKEN_BYTES = 32;
    private const TTL_HOURS = 48;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createTokenForUser(User $user): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));

        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationTokenExpiresAt(
            new \DateTimeImmutable(sprintf('+%d hours', self::TTL_HOURS)),
        );
        $user->setIsEmailVerified(false);

        $this->entityManager->flush();

        return $token;
    }

    public function isTokenValid(User $user): bool
    {
        if ($user->getEmailVerificationToken() === null) {
            return false;
        }

        $expiresAt = $user->getEmailVerificationTokenExpiresAt();

        return $expiresAt instanceof \DateTimeImmutable && $expiresAt > new \DateTimeImmutable();
    }

    public function clearToken(User $user): void
    {
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationTokenExpiresAt(null);
        $user->setIsEmailVerified(true);
    }

    public function regenerateToken(User $user): string
    {
        return $this->createTokenForUser($user);
    }
}
