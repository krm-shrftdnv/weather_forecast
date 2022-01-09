<?php


namespace App\Component\GeoHelper;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoHelper
{
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
    ) {
        $this->httpClient = $httpClient;
    }

    public function getCityCoordinates(string $cityName): array
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('https://nominatim.openstreetmap.org/search?city=%s&format=json', $cityName)
        );
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            if (is_array($data) && count($data) > 0) {
                $latitude = $data[0]['lat'];
                $longitude = $data[0]['lon'];
                return [$latitude, $longitude];
            } else {
                throw new HttpException(Response::HTTP_NOT_FOUND, 'Город не найден.');
            }
        } else {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, json_encode($response->getInfo()));
        }
    }
}