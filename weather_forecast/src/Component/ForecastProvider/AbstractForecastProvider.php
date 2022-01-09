<?php


namespace App\Component\ForecastProvider;


use App\Repository\ForecastRepository;
use App\Service\Dto\ForecastDto;
use DateInterval;
use DateTime;
use DateTimeZone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractForecastProvider implements ForecastProviderInterface
{

    public ForecastRepository $forecastRepository;
    public HttpClientInterface $client;
    public ParameterBagInterface $parameterBag;

    /**
     * @inheritDoc
     */
    public function getForecast(float $latitude, float $longitude, string $cityName, string $provider, int $countDays = 1): array
    {
        $now = new DateTime(timezone: new DateTimeZone("Europe/Moscow"));
        $oldForecasts = $this->forecastRepository
            ->createQueryBuilder('f')
            ->where('f.createdAt >= :today')
            ->andWhere('f.city = :city')
            ->andWhere('f.provider = :provider')
            ->andWhere('f.forecastDateTime BETWEEN :start AND :end')
            ->setParameter('today', $now->format('Y-m-d 00:00:00'))
            ->setParameter('city', $cityName)
            ->setParameter('provider', $provider)
            ->setParameter('start', $now->format('Y-m-d 00:00:00'))
            ->setParameter('end',
                $now
                    ->add(new DateInterval(sprintf('P%dD', $countDays - 1)))
                    ->format('Y-m-d 23:59:59'))
            ->orderBy('f.forecastDateTime')
            ->getQuery()
            ->execute();
        if (count($oldForecasts) >= $countDays) {
            return ForecastDto::fromMany($oldForecasts);
        }
        return static::getForecastByRequest($latitude, $longitude, $cityName, $countDays);
    }
}