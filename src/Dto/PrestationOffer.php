<?php
namespace App\Dto;
use App\Entity\Cat;
use App\Entity\Location;
use App\Entity\Service;
use App\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
/**
 * DTO PrestationOffer : objet de transfert pour une offre de prestation complète.
 * Regroupe une prestation (Service), un salon (Location) et un chat masseur (Cat)
 * disponibles ensemble — utile pour les résultats de recherche et la réservation.
 */
readonly class PrestationOffer
{
    /**
     * @param Service $service La prestation de massage proposée
     * @param Location $location Le salon où elle a lieu
     * @param Cat $cat Le chat masseur qui la réalise
     */
    public function __construct(
        #[Groups([SerializationGroups::API_READ])]
        public Service $service,
        #[Groups([SerializationGroups::API_READ])]
        public Location $location,
        #[Groups([SerializationGroups::API_READ])]
        public Cat $cat,
    ) {
    }
}
