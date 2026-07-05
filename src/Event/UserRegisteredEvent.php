<?php

namespace App\Event;

use App\Entity\User;

/**
 * Événement métier déclenché après l'inscription d'un nouvel utilisateur.
 */
final class UserRegisteredEvent
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
