<?php


namespace App\Component\ForecastProvider;


use App\Service\Dto\ForecastDto;

interface ForecastProviderInterface
{
    /** @return ForecastDto[] */
    public function getForecast(float $latitude, float $longitude, string $cityName, string $provider, int $countDays = 1): array;

    /** @return ForecastDto[] */
    public function getForecastByRequest(float $latitude, float $longitude, string $cityName, int $countDays = 1): array;
}