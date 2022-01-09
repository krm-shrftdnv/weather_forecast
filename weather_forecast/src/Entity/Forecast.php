<?php


namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ForecastRepository;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=ForecastRepository::class)
 * @ORM\Table(name="forecast")
 */
class Forecast
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $tempNight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $tempDay;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private float $windSpeed;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $forecastDateTime;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $provider;

    private bool $isNew = false;

    public function __construct()
    {
        $this->createdAt = new DateTime(timezone: new DateTimeZone("Europe/Moscow"));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setForecastDateTime(DateTime $forecastDateTime): void
    {
        $this->forecastDateTime = $forecastDateTime;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function setIsNew(bool $isNew): void
    {
        $this->isNew = $isNew;
    }

    public function getForecastDateTime(): DateTime
    {
        return $this->forecastDateTime;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function getTempNight(): ?int
    {
        return $this->tempNight ?? null;
    }

    public function setTempNight(int $tempNight): void
    {
        $this->tempNight = $tempNight;
    }

    public function getTempDay(): ?int
    {
        return $this->tempDay ?? null;
    }

    public function setTempDay(int $tempDay): void
    {
        $this->tempDay = $tempDay;
    }

    public function getWindSpeed(): ?float
    {
        return $this->windSpeed ?? null;
    }

    public function setWindSpeed(float $windSpeed): void
    {
        $this->windSpeed = $windSpeed;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getProvider(): ?string
    {
        return $this->provider ?? null;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }
}