<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate;

/**
 * Body that issued the certificate (wire "codeOrganismeEmetteur", "libelleOrganismeEmetteur").
 */
final readonly class IssuingBody
{
    private function __construct(private ?string $code, private ?string $label) {}

    public static function of(?string $code, ?string $label): self
    {
        return new self($code, $label);
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function label(): ?string
    {
        return $this->label;
    }
}
