<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for currency data (nested in country requests).
 */
class CurrencyRequest
{
    #[Assert\Length(max: 255, maxMessage: 'Currency name cannot be longer than 255 characters')]
    public ?string $name = null;

    #[Assert\Length(max: 10, maxMessage: 'Currency symbol cannot be longer than 10 characters')]
    public ?string $symbol = null;

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->name = $data['name'] ?? null;
        $request->symbol = $data['symbol'] ?? null;

        return $request;
    }
}
