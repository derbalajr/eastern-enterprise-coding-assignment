<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Embeddable]
class Currency
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $symbol = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(?string $symbol): void
    {
        $this->symbol = $symbol;
    }
}