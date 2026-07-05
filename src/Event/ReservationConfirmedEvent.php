<?php

namespace App\Event;

use App\Entity\Reservation;

/**
 * Événement métier déclenché après la confirmation d'une réservation.
 */
final class ReservationConfirmedEvent
{
    public function __construct(
        public readonly Reservation $reservation,
    ) {
    }
}
