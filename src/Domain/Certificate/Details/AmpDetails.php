<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details;

/**
 * Wire key "infosSecurisationSpecifiquesAMP": siren, nir, etablissement[].
 * Personal data is masked in __debugInfo().
 */
final readonly class AmpDetails implements CertificateDetails
{
    /**
     * @param list<Establishment> $establishments
     */
    private function __construct(
        private ?string $siren,
        private ?string $nir,
        private array $establishments,
    ) {}

    /**
     * @param list<Establishment> $establishments
     */
    public static function of(
        ?string $siren,
        ?string $nir,
        array $establishments,
    ): self {
        return new self($siren, $nir, $establishments);
    }

    public function siren(): ?string
    {
        return $this->siren;
    }

    public function nir(): ?string
    {
        return $this->nir;
    }

    /**
     * @return list<Establishment>
     */
    public function establishments(): array
    {
        return $this->establishments;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'siren' => $this->siren,
            'nir' => $this->nir === null ? null : '***',
            'establishments' => $this->establishments,
        ];
    }
}
