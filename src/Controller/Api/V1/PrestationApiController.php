<?php

namespace App\Controller\Api\V1;

use App\Dto\Api\PrestationSearchQuery;
use App\Entity\Service;
use App\Repository\LocationRepository;
use App\Repository\ServiceRepository;
use App\Serializer\SerializationGroups;
use App\Service\ApiSerializerService;
use App\Service\PrestationSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API REST v1 — prestations et recherche, réponses JSON via le Serializer Symfony.
 */
#[Route('/api/v1')]
#[IsGranted('ROLE_USER')]
final class PrestationApiController extends AbstractController
{
    /**
     * Catalogue des prestations globales (vue liste).
     */
    #[Route('/prestations', name: 'api_v1_prestations_list', methods: ['GET'])]
    public function list(
        ServiceRepository $serviceRepository,
        ApiSerializerService $apiSerializer,
    ): Response {
        $services = $serviceRepository->findGlobalServices();

        return new JsonResponse(
            $apiSerializer->normalize($services, [SerializationGroups::API_READ]),
        );
    }

    /**
     * Détail d'une prestation (groupes de normalisation enrichis).
     */
    #[Route('/prestations/{id}', name: 'api_v1_prestations_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Service $service,
        ApiSerializerService $apiSerializer,
    ): Response {
        return new JsonResponse(
            $apiSerializer->normalize($service, [
                SerializationGroups::API_READ,
                SerializationGroups::API_READ_DETAIL,
            ]),
        );
    }

    /**
     * Recherche de prestations : dénormalisation du corps JSON puis normalisation des offres.
     */
    #[Route('/prestations/search', name: 'api_v1_prestations_search', methods: ['POST'])]
    public function search(
        Request $request,
        PrestationSearchService $prestationSearchService,
        LocationRepository $locationRepository,
        ServiceRepository $serviceRepository,
        ApiSerializerService $apiSerializer,
        ValidatorInterface $validator,
    ): Response {
        try {
            /** @var PrestationSearchQuery $searchQuery */
            $searchQuery = $apiSerializer->denormalize(
                json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR),
                PrestationSearchQuery::class,
                [SerializationGroups::API_WRITE],
            );
        } catch (\JsonException) {
            return $this->jsonError('Corps JSON invalide.', Response::HTTP_BAD_REQUEST);
        } catch (NotEncodableValueException|PartialDenormalizationException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $violations = $validator->validate($searchQuery);
        if ($violations->count() > 0) {
            return $this->jsonError((string) $violations, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $location = $searchQuery->locationId !== null
            ? $locationRepository->find($searchQuery->locationId)
            : null;
        $service = $searchQuery->serviceId !== null
            ? $serviceRepository->find($searchQuery->serviceId)
            : null;

        if ($searchQuery->locationId !== null && $location === null) {
            return $this->jsonError('Salon introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($searchQuery->serviceId !== null && $service === null) {
            return $this->jsonError('Prestation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $offers = $prestationSearchService->search(
            $location,
            $service,
            $searchQuery->query,
        );

        return new JsonResponse(
            $apiSerializer->normalize($offers, [SerializationGroups::API_READ]),
        );
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
