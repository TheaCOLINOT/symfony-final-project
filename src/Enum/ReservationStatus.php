<?php
namespace App\Enum;
/**
 * Enum ReservationStatus : états possibles d'une réservation de massage.
 * Pour l'instant seul le statut "confirmée" existe, mais on pourra en ajouter
 * d'autres plus tard (annulée, en attente, etc.).
 */
enum ReservationStatus: string
{
    case Confirmed = 'confirmed'; // La réservation est validée
    /**
     * Retourne un libellé lisible en français pour afficher le statut.
     */
    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmée',
        };
    }
}
