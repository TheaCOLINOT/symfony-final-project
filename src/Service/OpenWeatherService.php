<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Récupère la météo en temps réel via l'API OpenWeatherMap.
 */
final class OpenWeatherService
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire('%env(OPENWEATHER_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(OPENWEATHER_DEFAULT_CITY)%')]
        private readonly string $defaultCity,
    ) {
    }

    /**
     * @return array{
     *     city: string,
     *     temperature: float,
     *     description: string,
     *     icon: string,
     *     condition: string,
     *     weatherId: int
     * }
     */
    public function getCurrentWeather(?float $latitude = null, ?float $longitude = null, ?string $city = null): array
    {
        if ($this->apiKey === '') {
            return $this->getDemoWeather($city ?? $this->defaultCity);
        }

        $cacheKey = sprintf(
            'openweather.%s',
            md5(json_encode([$latitude, $longitude, $city ?? $this->defaultCity], JSON_THROW_ON_ERROR)),
        );

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($latitude, $longitude, $city): array {
            $item->expiresAfter(600);

            return $this->fetchFromApi($latitude, $longitude, $city);
        });
    }

    /**
     * @return array{
     *     city: string,
     *     temperature: float,
     *     description: string,
     *     icon: string,
     *     condition: string,
     *     weatherId: int
     * }
     */
    private function fetchFromApi(?float $latitude, ?float $longitude, ?string $city): array
    {
        $query = [
            'appid' => $this->apiKey,
            'units' => 'metric',
            'lang' => 'fr',
        ];

        if ($latitude !== null && $longitude !== null) {
            $query['lat'] = (string) $latitude;
            $query['lon'] = (string) $longitude;
        } else {
            $query['q'] = $city ?? $this->defaultCity;
        }

        $response = $this->httpClient->request('GET', self::API_URL, [
            'query' => $query,
            'timeout' => 5,
        ]);

        $data = $response->toArray();
        $weather = $data['weather'][0] ?? [];

        return [
            'city' => (string) ($data['name'] ?? $city ?? $this->defaultCity),
            'temperature' => round((float) ($data['main']['temp'] ?? 0), 1),
            'description' => (string) ($weather['description'] ?? 'conditions inconnues'),
            'icon' => (string) ($weather['icon'] ?? '01d'),
            'condition' => (string) ($weather['main'] ?? 'Unknown'),
            'weatherId' => (int) ($weather['id'] ?? 0),
        ];
    }

    /**
     * Données de démo lorsque la clé API n'est pas configurée.
     *
     * @return array{
     *     city: string,
     *     temperature: float,
     *     description: string,
     *     icon: string,
     *     condition: string,
     *     weatherId: int
     * }
     */
    private function getDemoWeather(string $city): array
    {
        return [
            'city' => $city,
            'temperature' => 19.0,
            'description' => 'ciel dégagé (démo)',
            'icon' => '01d',
            'condition' => 'Clear',
            'weatherId' => 800,
        ];
    }
}
