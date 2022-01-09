<?php


namespace App\Component\ForecastProvider;


use App\Component\Client\YandexForecastClient;
use App\Entity\Forecast;
use App\Repository\ForecastRepository;
use App\Service\Dto\ForecastDto;
use DateInterval;
use DateTime;
use DateTimeZone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YandexForecastProvider extends AbstractForecastProvider
{
    public ForecastRepository $forecastRepository;
    public HttpClientInterface $client;
    public ParameterBagInterface $parameterBag;

    /** @inheritDoc */
    public function getForecastByRequest(float $latitude, float $longitude, string $cityName, int $countDays = 1): array
    {
        $client = new YandexForecastClient(
            parameterBag: $this->parameterBag,
            client: $this->client
        );
        try {
            $response = $client->request(
                method: 'GET',
                url: 'https://api.weather.yandex.ru/v2/forecast',
                query: [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'limit' => $countDays,
                ]
            );
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $forecasts = [];
                if (isset($data['forecasts'])) {
                    foreach ($data['forecasts'] as $forecastData) {
                        $forecast = new Forecast();
                        $forecast->setDescription(json_encode($forecastData, JSON_UNESCAPED_UNICODE));
                        $forecast->setTempNight($forecastData['parts']['night']['temp_avg']);
                        $forecast->setTempDay($forecastData['parts']['day']['temp_avg']);
                        $forecast->setWindSpeed($forecastData['parts']['day']['wind_speed']);
                        $forecast->setIsNew(true);
                        $forecast->setForecastDateTime(DateTime::createFromFormat('Y-m-d', $forecastData['date'], new DateTimeZone("Europe/Moscow")));
                        $forecast->setCity($cityName);
                        $forecasts[] = ForecastDto::from($forecast);
                    }
                }
                return $forecasts;
            } else {
                throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, json_encode($response->getInfo()));
            }

        } catch (TransportExceptionInterface $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}