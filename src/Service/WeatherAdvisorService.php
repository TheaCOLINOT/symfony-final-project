<?php

namespace App\Service;

use App\Dto\WeatherAdvice;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Transforme la météo OpenWeather en recommandation du chaton assistant.
 */
final class WeatherAdvisorService
{
    /** @var list<string> */
    private const BAD_CONDITIONS = [
        'Thunderstorm',
        'Drizzle',
        'Rain',
        'Snow',
        'Mist',
        'Fog',
        'Squall',
        'Tornado',
        'Hail',
        'Smoke',
        'Dust',
        'Sand',
        'Ash',
    ];

    public function __construct(
        private readonly OpenWeatherService $openWeatherService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function advise(?float $latitude = null, ?float $longitude = null, ?string $city = null): WeatherAdvice
    {
        $weather = $this->openWeatherService->getCurrentWeather($latitude, $longitude, $city);
        $isGoodWeather = $this->isGoodWeather($weather['condition'], $weather['weatherId']);

        if ($isGoodWeather) {
            return new WeatherAdvice(
                city: $weather['city'],
                temperature: $weather['temperature'],
                description: $weather['description'],
                icon: $weather['icon'],
                condition: $weather['condition'],
                isGoodWeather: true,
                kittenMessage: sprintf(
                    'Miaou ! Il fait %s à %s (%.0f °C). Profitez du beau temps pour vous détendre dans l\'un de nos salons !',
                    $weather['description'],
                    $weather['city'],
                    $weather['temperature'],
                ),
                actionLabel: 'Trouver un salon',
                actionUrl: $this->urlGenerator->generate('app_search'),
            );
        }

        return new WeatherAdvice(
            city: $weather['city'],
            temperature: $weather['temperature'],
            description: $weather['description'],
            icon: $weather['icon'],
            condition: $weather['condition'],
            isGoodWeather: false,
            kittenMessage: sprintf(
                'Mrrr… %s à %s (%.0f °C). Restez bien au chaud chez vous et commandez une prestation à distance !',
                ucfirst($weather['description']),
                $weather['city'],
                $weather['temperature'],
            ),
            actionLabel: 'Prestation à domicile',
            actionUrl: $this->urlGenerator->generate('app_search', ['q' => 'live chat']),
        );
    }

    private function isGoodWeather(string $condition, int $weatherId): bool
    {
        if (\in_array($condition, self::BAD_CONDITIONS, true)) {
            return false;
        }

        if ($condition === 'Clear') {
            return true;
        }

        if ($condition === 'Clouds') {
            return $weatherId <= 801;
        }

        return false;
    }
}
