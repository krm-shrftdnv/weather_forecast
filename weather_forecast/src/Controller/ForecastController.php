<?php


namespace App\Controller;


use App\Service\ForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ForecastController extends AbstractController
{
    private ForecastService $forecastService;
    private Serializer $serializer;

    public function __construct(ForecastService $forecastService)
    {
        $dateCallback = function (
            $innerObject,
            $outerObject,
            string $attributeName,
            string $format = null,
            array $context = []
        ) {
            return $innerObject instanceof \DateTime ? $innerObject->format('d.m.Y') : '';
        };
        $defaultContext = [
            AbstractNormalizer::CALLBACKS => [
                'createdAt' => $dateCallback,
                'forecastDateTime' => $dateCallback,
            ],
            AbstractNormalizer::IGNORED_ATTRIBUTES => [
                'description',
                'new',
            ]
        ];

        $this->forecastService = $forecastService;
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ObjectNormalizer(
                nameConverter: new CamelCaseToSnakeCaseNameConverter(),
                defaultContext: $defaultContext
            )
        ];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * @Route(path="/api/forecast", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $providerType = $request->query->get('forecast_provider_type');
        $countDays = $request->query->get('period');
        $cityName = $request->query->get('city');
        $forecasts = $this->forecastService->getForecasts(
            forecastProviderType: $providerType,
            cityName: $cityName,
            countDays: $countDays,
        );
        return new JsonResponse(
            data: $this->serializer->serialize($forecasts, 'json', ['json_encode_options' => JSON_UNESCAPED_UNICODE]),
            json: true
        );
    }
}