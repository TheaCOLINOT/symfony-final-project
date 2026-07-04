<?php

namespace App\Service;

use App\Entity\Cat;
use App\Entity\Location;
use App\Entity\Reservation;
use App\Entity\Service;
use App\Entity\User;
use App\Enum\ReservationStatus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service qui crée une réservation à partir d'une offre (prestation + salon + masseur).
 * Vérifie d'abord que la combinaison est bien valide.
 */
final class ReservationFactoryService
{
    /**
     * Vérifie que le masseur propose bien la prestation dans ce salon.
     * Lève une 404 si l'offre n'existe pas ou n'est pas cohérente.
     */
    public function assertOfferAvailable(Service $service, Location $location, Cat $cat): void
    {
        if ($service->isRemoteLiveChat()) {
            $this->assertRemoteOfferAvailable($service, $location, $cat);

            return;
        }

        if ($location->isGlobal() || $location->isRemote()) {
            throw new NotFoundHttpException('Cette prestation n\'est pas disponible.');
        }

        if (!$cat->isInLocation($location)) {
            throw new NotFoundHttpException('Ce masseur chat n\'est pas disponible dans ce salon.');
        }

        if (!$cat->getServices()->contains($service)) {
            throw new NotFoundHttpException('Ce masseur chat ne propose pas cette prestation.');
        }
    }

    /** Live chat à distance : tous les chats, lieu virtuel uniquement. */
    public function assertRemoteOfferAvailable(Service $service, Location $location, Cat $cat): void
    {
        if (!$service->isRemoteLiveChat()) {
            throw new NotFoundHttpException('Cette prestation n\'est pas un live chat à distance.');
        }

        if (!$location->isRemote()) {
            throw new NotFoundHttpException('Cette prestation se fait uniquement à distance.');
        }
    }

    /**
     * Crée une réservation confirmée avec les infos figées (labels, prix, durée).
     */
    public function create(
        User $user,
        Service $service,
        Location $location,
        Cat $cat,
        \DateTimeInterface $reservationDate,
    ): Reservation {
        $this->assertOfferAvailable($service, $location, $cat);

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setService($service);
        $reservation->setLocation($location);
        $reservation->setReservationDate($reservationDate);
        $reservation->setServiceLabel($service->getTitle());
        $reservation->setCatLabel(sprintf(
            '%s (%s)',
            $cat->getSpeciality() ?? 'Masseur',
            $cat->getSpecie() ?? 'chat'
        ));
        $reservation->setDuration($service->getDuration());
        $reservation->setPrice($service->getPrice());
        $reservation->setStatus(ReservationStatus::Confirmed);
        $reservation->addCat($cat);

        return $reservation;
    }
}
