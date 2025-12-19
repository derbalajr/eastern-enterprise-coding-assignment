<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'countries')]
#[ORM\HasLifecycleCallbacks]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    #[Groups(['country:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 36)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['country:read'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $region = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'sub_region')]
    #[Groups(['country:read'])]
    private ?string $subRegion = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $demonym = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Groups(['country:read'])]
    private ?int $population = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['country:read'])]
    private ?bool $independent = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $flag = null;

    #[ORM\Embedded(class: Currency::class, columnPrefix: 'currency_')]
    #[Groups(['country:read'])]
    private ?Currency $currency = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'updated_at')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    public function getSubRegion(): ?string
    {
        return $this->subRegion;
    }

    public function setSubRegion(?string $subRegion): void
    {
        $this->subRegion = $subRegion;
    }

    public function getDemonym(): ?string
    {
        return $this->demonym;
    }

    public function setDemonym(?string $demonym): void
    {
        $this->demonym = $demonym;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(?int $population): void
    {
        $this->population = $population;
    }

    public function isIndependent(): ?bool
    {
        return $this->independent;
    }

    public function setIndependent(?bool $independent): void
    {
        $this->independent = $independent;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(?string $flag): void
    {
        $this->flag = $flag;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
