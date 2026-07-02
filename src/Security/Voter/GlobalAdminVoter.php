<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\ManagerContextService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
final class GlobalAdminVoter extends Voter
{
    public const GLOBAL_ADMIN = 'GLOBAL_ADMIN';

    public function __construct(
        private readonly ManagerContextService $managerContextService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::GLOBAL_ADMIN;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $this->managerContextService->isGlobalAdmin($user);
    }
}
