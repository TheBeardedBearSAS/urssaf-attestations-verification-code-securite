<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details;

/**
 * Establishment of a certificate holder (wire "etablissement[]": "siret", "adresse").
 */
final readonly class Establishment
{
    private function __construct(private ?string $siret, private ?string $address) {}

    public static function of(?string $siret, ?string $address): self
    {
        return new self($siret, $address);
    }

    public function siret(): ?string
    {
        return $this->siret;
    }

    public function address(): ?string
    {
        return $this->address;
    }
}
