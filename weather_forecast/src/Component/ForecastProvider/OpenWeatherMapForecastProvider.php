<?php


namespace App\Component\ForecastProvider;


use App\Component\Client\OpenWeatherMapForecastClient;
use App\Entity\Forecast;
use App\Service\Dto\ForecastDto;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class OpenWeatherMapForecastProvider extends AbstractForecastProvider
{

    /**
     * @inheritDoc
     */
    public function getForecastByRequest(float $latitude, float $longitude, string $cityName, int $countDays = 1): array
    {
        $client = new OpenWeatherMapForecastClient(
            parameterBag: $this->parameterBag,
            client: $this->client,
        );
        try {
            $response = $client->request(
                method: 'GET',
                url: 'https://api.openweathermap.org/data/2.5/onecall',
                query: [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'cnt' => $countDays,
                    'units' => 'metric',
                ]
            );
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $forecasts = [];
                if (isset($data['daily'])) {
                    foreach ($data['daily'] as $forecastData) {
                        $forecast = new Forecast();
                        $forecast->setDescription(json_encode($forecastData, JSON_UNESCAPED_UNICODE));
                        $forecast->setTempNight($forecastData['temp']['night']);
                        $forecast->setTempDay($forecastData['temp']['day']);
                        $forecast->setWindSpeed($forecastData['wind_speed']);
                        $forecast->setIsNew(true);
                        $forecast->setForecastDateTime(DateTime::createFromFormat('U', $forecastData['dt'], new DateTimeZone("Europe/Moscow")));
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