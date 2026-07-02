<?php

namespace App\Service;

use App\Entity\Location;
use App\Entity\Manager;
use App\Entity\User;
use App\Repository\LocationRepository;
use App\Repository\ManagerRepository;

final class ManagerContextService
{
    public function __construct(
        private readonly ManagerRepository $managerRepository,
        private readonly LocationRepository $locationRepository,
    ) {
    }

    public function getManagerProfile(User $user): ?Manager
    {
        return $this->managerRepository->findOneByUser($user);
    }

    public function getManagedLocation(User $user): ?Location
    {
        return $this->getManagerProfile($user)?->getLocation();
    }

    public function isGlobalAdmin(User $user): bool
    {
        $location = $this->getManagedLocation($user);

        return $location !== null && $location->isGlobal();
    }

    public function getGlobalLocation(): ?Location
    {
        return $this->locationRepository->findGlobalLocation();
    }

    public function canManageLocation(User $user, Location $location): bool
    {
        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        $managedLocation = $this->getManagedLocation($user);

        return $managedLocation !== null && $managedLocation->getId() === $location->getId();
    }
}
