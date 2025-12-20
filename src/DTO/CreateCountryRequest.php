<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for creating a country (similar to Laravel Form Request).
 */
class CreateCountryRequest
{
    #[Assert\NotBlank(message: 'UUID is required')]
    #[Assert\Length(max: 36, maxMessage: 'UUID cannot be longer than 36 characters')]
    public ?string $uuid = null;

    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(max: 255, maxMessage: 'Name cannot be longer than 255 characters')]
    public ?string $name = null;

    #[Assert\Length(max: 255, maxMessage: 'Region cannot be longer than 255 characters')]
    public ?string $region = null;

    #[Assert\Length(max: 255, maxMessage: 'Sub region cannot be longer than 255 characters')]
    public ?string $subRegion = null;

    #[Assert\Length(max: 255, maxMessage: 'Demonym cannot be longer than 255 characters')]
    public ?string $demonym = null;

    #[Assert\Type(type: 'integer', message: 'Population must be an integer')]
    #[Assert\PositiveOrZero(message: 'Population must be positive or zero')]
    public ?int $population = null;

    #[Assert\Type(type: 'boolean', message: 'Independent must be a boolean')]
    public ?bool $independent = null;

    #[Assert\Length(max: 500, maxMessage: 'Flag URL cannot be longer than 500 characters')]
    #[Assert\Url(message: 'Flag must be a valid URL')]
    public ?string $flag = null;

    #[Assert\Valid]
    public ?CurrencyRequest $currency = null;

    /**
     * Create from array (for JSON deserialization).
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->uuid = $data['uuid'] ?? null;
        $request->name = $data['name'] ?? null;
        $request->region = $data['region'] ?? null;
        $request->subRegion = $data['subRegion'] ?? null;
        $request->demonym = $data['demonym'] ?? null;
        $request->population = $data['population'] ?? null;
        $request->independent = $data['independent'] ?? null;
        $request->flag = $data['flag'] ?? null;

        if (isset($data['currency']) && is_array($data['currency'])) {
            $request->currency = CurrencyRequest::fromArray($data['currency']);
        }

        return $request;
    }
}
