<?php

namespace App\Controller\Api\V1;

use App\Service\WeatherAdvisorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Météo temps réel pour le chatbot assistant (accès public).
 */
#[Route('/api/v1')]
final class WeatherApiController extends AbstractController
{
    #[Route('/weather', name: 'api_v1_weather', methods: ['GET'])]
    public function current(Request $request, WeatherAdvisorService $weatherAdvisor): JsonResponse
    {
        $latRaw = $request->query->get('lat');
        $lonRaw = $request->query->get('lon');
        $city = $request->query->get('city');

        $latitude = is_numeric($latRaw) ? (float) $latRaw : null;
        $longitude = is_numeric($lonRaw) ? (float) $lonRaw : null;
        $cityName = \is_string($city) && trim($city) !== '' ? trim($city) : null;

        try {
            $advice = $weatherAdvisor->advise($latitude, $longitude, $cityName);

            return new JsonResponse($advice->toArray());
        } catch (\Throwable) {
            return new JsonResponse([
                'error' => 'Impossible de récupérer la météo pour le moment.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
