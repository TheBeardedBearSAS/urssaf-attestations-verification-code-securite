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

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\Instant;

/**
 * Wire key "infosSecurisationSpecifiquesASAA": nomArtisteAuteur, prenomArtisteAuteur, dateDebutDeclaration, dateFinDeclaration.
 * Personal data is masked in __debugInfo().
 */
final readonly class AsaaDetails implements CertificateDetails
{
    private function __construct(
        private ?string $lastName,
        private ?string $firstName,
        private ?Instant $declarationStart,
        private ?Instant $declarationEnd,
    ) {}

    public static function of(
        ?string $lastName,
        ?string $firstName,
        ?Instant $declarationStart,
        ?Instant $declarationEnd,
    ): self {
        return new self($lastName, $firstName, $declarationStart, $declarationEnd);
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function declarationStart(): ?Instant
    {
        return $this->declarationStart;
    }

    public function declarationEnd(): ?Instant
    {
        return $this->declarationEnd;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'lastName' => $this->lastName === null ? null : '***',
            'firstName' => $this->firstName === null ? null : '***',
            'declarationStart' => $this->declarationStart,
            'declarationEnd' => $this->declarationEnd,
        ];
    }
}
