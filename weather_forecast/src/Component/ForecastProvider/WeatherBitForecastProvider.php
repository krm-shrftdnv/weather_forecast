<?php


namespace App\Component\ForecastProvider;


use App\Component\Client\WeatherBitForecastClient;
use App\Entity\Forecast;
use App\Service\Dto\ForecastDto;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WeatherBitForecastProvider extends AbstractForecastProvider
{

    /**
     * @inheritDoc
     */
    public function getForecastByRequest(float $latitude, float $longitude, string $cityName, int $countDays = 1): array
    {
        $client = new WeatherBitForecastClient(
            parameterBag: $this->parameterBag,
            client: $this->client,
        );
        try {
            $response = $client->request(
                method: 'GET',
                url: 'https://api.weatherbit.io/v2.0/forecast/daily',
                query: [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'days' => $countDays,
                ]
            );
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $forecasts = [];
                if (isset($data['data'])) {
                    foreach ($data['data'] as $forecastData) {
                        $forecast = new Forecast();
                        $forecast->setDescription(json_encode($forecastData, JSON_UNESCAPED_UNICODE));
                        $forecast->setTempNight($forecastData['min_temp']);
                        $forecast->setTempDay($forecastData['max_temp']);
                        $forecast->setWindSpeed($forecastData['wind_spd']);
                        $forecast->setIsNew(true);
                        $forecast->setForecastDateTime(DateTime::createFromFormat('Y-m-d', $forecastData['valid_date'],
                            new DateTimeZone("Europe/Moscow")));
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