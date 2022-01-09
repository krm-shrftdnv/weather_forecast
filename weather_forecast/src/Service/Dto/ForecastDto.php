<?php


namespace App\Service\Dto;


use App\Entity\Forecast;
use DateTime;

class ForecastDto
{
    public function __construct(
        public string $description,
        public ?int $tempNight,
        public ?int $tempDay,
        public ?float $windSpeed,
        public DateTime $createdAt,
        public DateTime $forecastDateTime,
        public bool $isNew,
        public string $city,
        public ?string $provider,
    ) {
    }

    public static function from(Forecast $forecast): ForecastDto
    {
        return new ForecastDto(
            description: $forecast->getDescription(),
            tempNight: $forecast->getTempNight(),
            tempDay: $forecast->getTempDay(),
            windSpeed: $forecast->getWindSpeed(),
            createdAt: $forecast->getCreatedAt(),
            forecastDateTime: $forecast->getForecastDateTime(),
            isNew: $forecast->isNew(),
            city: $forecast->getCity(),
            provider: $forecast->getProvider(),
        );
    }

    /**
     * @return ForecastDto[]
     * @param Forecast[] $forecasts
     */
    public static function fromMany(array $forecasts): array
    {
        return array_map(fn($forecast) => ForecastDto::from($forecast), $forecasts);
    }
}