<?php

namespace App\Dto\Api;

use App\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload JSON pour la recherche de prestations via l'API.
 */
final class PrestationSearchQuery
{
    #[Groups([SerializationGroups::API_WRITE])]
    #[Assert\Positive]
    public ?int $locationId = null;

    #[Groups([SerializationGroups::API_WRITE])]
    #[Assert\Positive]
    public ?int $serviceId = null;

    #[Groups([SerializationGroups::API_WRITE])]
    #[Assert\Length(max: 255)]
    public ?string $query = null;
}
