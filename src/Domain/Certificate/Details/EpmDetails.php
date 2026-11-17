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
 * Wire key "infosSecurisationSpecifiquesEPM": etablissement[].
 */
final readonly class EpmDetails implements CertificateDetails
{
    /**
     * @param list<Establishment> $establishments
     */
    private function __construct(
        private array $establishments,
    ) {}

    /**
     * @param list<Establishment> $establishments
     */
    public static function of(
        array $establishments,
    ): self {
        return new self($establishments);
    }

    /**
     * @return list<Establishment>
     */
    public function establishments(): array
    {
        return $this->establishments;
    }
}
