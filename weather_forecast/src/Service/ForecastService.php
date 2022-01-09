<?php


namespace App\Service;


use App\Component\ForecastProvider\AbstractForecastProvider;
use App\Component\ForecastProvider\OpenWeatherMapForecastProvider;
use App\Component\ForecastProvider\WeatherBitForecastProvider;
use App\Component\ForecastProvider\YandexForecastProvider;
use App\Component\GeoHelper\GeoHelper;
use App\Entity\Forecast;
use App\Enum\ForecastProviderTypesEnum;
use App\Query\CastAsDate;
use App\Repository\ForecastRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ForecastService
{
    private ForecastRepository $forecastRepository;
    private HttpClientInterface $client;
    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $entityManager;
    private GeoHelper $geoHelper;

    public function __construct(
        ForecastRepository $forecastRepository,
        HttpClientInterface $client,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        GeoHelper $geoHelper,
    ) {
        $this->forecastRepository = $forecastRepository;
        $this->client = $client;
        $this->parameterBag = $parameterBag;
        $this->entityManager = $entityManager;
        $this->geoHelper = $geoHelper;
    }


    /**
     * @return Forecast[]
     * @param string $cityName
     * @param int $countDays
     * @param string $forecastProviderType
     */
    public function getForecasts(
        string $forecastProviderType,
        string $cityName,
        int $countDays = 1,
    ): array {
        $forecastProvider = self::getForecastProviderByName($forecastProviderType);
        [$latitude, $longitude] = $this->geoHelper->getCityCoordinates($cityName);
        $forecastsDtos = $forecastProvider->getForecast(
            latitude: $latitude,
            longitude: $longitude,
            cityName: $cityName,
            provider: $forecastProviderType,
            countDays: $countDays,
        );
        $forecasts = [];
        $this->entityManager->beginTransaction();
        $dateTimes = array_map(fn($forecastDto) => $forecastDto->forecastDateTime->format('Y-m-d'),
            array_filter($forecastsDtos, fn($forecastDto) => $forecastDto->isNew));

        if (count($dateTimes) > 0) {
            $config = $this->entityManager->getConfiguration();
            $config->addCustomDatetimeFunction('DATE', CastAsDate::class);
            $oldForecasts = $this->forecastRepository
                ->createQueryBuilder('f')
                ->where('DATE(f.forecastDateTime) IN (:dateTimes)')
                ->andWhere('f.city = :city')
                ->andWhere('f.provider = :provider')
                ->setParameter('dateTimes', $dateTimes)
                ->setParameter('city', $cityName)
                ->setParameter('provider', $forecastProviderType)
                ->getQuery()
                ->execute();
            /** @var Forecast $oldForecast */
            foreach ($oldForecasts as $oldForecast) {
                $this->entityManager->remove($oldForecast);
            }
        }
        foreach ($forecastsDtos as $forecastDto) {
            $forecast = new Forecast();
            $forecast->setDescription($forecastDto->description);
            $forecast->setTempNight($forecastDto->tempNight);
            $forecast->setTempDay($forecastDto->tempDay);
            $forecast->setWindSpeed($forecastDto->windSpeed);
            $forecast->setForecastDateTime($forecastDto->forecastDateTime);
            $forecast->setCity($forecastDto->city);
            $forecast->setProvider($forecastProviderType);
            if ($forecastDto->isNew) {
                $this->entityManager->persist($forecast);
            }
            $forecasts[] = $forecast;
        }
        $this->entityManager->flush();
        $this->entityManager->commit();

        if (count($forecasts) > $countDays) {
            return self::getForecasts($forecastProviderType, $cityName, $countDays);
        }
        return $forecasts;
    }

    private function getForecastProviderByName(string $forecastProviderName): AbstractForecastProvider
    {
        $forecastProvider = match ($forecastProviderName) {
            ForecastProviderTypesEnum::YANDEX_PROVIDER => new YandexForecastProvider(),
            ForecastProviderTypesEnum::OPEN_WEATHER_MAP_PROVIDER => new OpenWeatherMapForecastProvider(),
            ForecastProviderTypesEnum::WEATHERBIT_PROVIDER => new WeatherBitForecastProvider(),
            default => throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR)
        };
        $forecastProvider->client = $this->client;
        $forecastProvider->forecastRepository = $this->forecastRepository;
        $forecastProvider->parameterBag = $this->parameterBag;
        return $forecastProvider;
    }

}