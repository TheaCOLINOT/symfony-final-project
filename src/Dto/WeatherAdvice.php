<?php

namespace App\Dto;

/**
 * Conseil météo du chatbot : données brutes + message du chaton.
 */
readonly class WeatherAdvice
{
    public function __construct(
        public string $city,
        public float $temperature,
        public string $description,
        public string $icon,
        public string $condition,
        public bool $isGoodWeather,
        public string $kittenMessage,
        public string $actionLabel,
        public string $actionUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'temperature' => $this->temperature,
            'description' => $this->description,
            'icon' => $this->icon,
            'condition' => $this->condition,
            'isGoodWeather' => $this->isGoodWeather,
            'kittenMessage' => $this->kittenMessage,
            'action' => [
                'label' => $this->actionLabel,
                'url' => $this->actionUrl,
            ],
        ];
    }
}
