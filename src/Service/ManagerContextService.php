<?php

namespace App\Service;

use App\Entity\Location;
use App\Entity\Manager;
use App\Entity\User;
use App\Repository\LocationRepository;
use App\Repository\ManagerRepository;

/**
 * Service qui centralise les infos du manager connecté.
 * Utile pour savoir quel salon il gère et s'il est admin global.
 */
final class ManagerContextService
{
    public function __construct(
        private readonly ManagerRepository $managerRepository,
        private readonly LocationRepository $locationRepository,
    ) {
    }

    /**
     * Récupère le profil manager lié à l'utilisateur (ou null si ce n'est pas un manager).
     */
    public function getManagerProfile(User $user): ?Manager
    {
        return $this->managerRepository->findOneByUser($user);
    }

    /**
     * Salon géré par le manager connecté (null s'il n'en a pas).
     */
    public function getManagedLocation(User $user): ?Location
    {
        return $this->getManagerProfile($user)?->getLocation();
    }

    /**
     * Vérifie si le manager est admin global (salon marqué isGlobal).
     */
    public function isGlobalAdmin(User $user): bool
    {
        $location = $this->getManagedLocation($user);

        return $location !== null && $location->isGlobal();
    }

    /**
     * Récupère le salon global de l'application (admin principal).
     */
    public function getGlobalLocation(): ?Location
    {
        return $this->locationRepository->findGlobalLocation();
    }

    /**
     * Indique si le manager peut agir sur un salon donné.
     * L'admin global peut tout gérer, sinon seulement son propre salon.
     */
    public function canManageLocation(User $user, Location $location): bool
    {
        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        $managedLocation = $this->getManagedLocation($user);

        return $managedLocation !== null && $managedLocation->getId() === $location->getId();
    }
}
