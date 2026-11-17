<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;

/**
 * Builds the body of `POST /codes_securite/verifier` (api-reference section 2.2). Order and duplicates are kept.
 */
final readonly class VerifyManyRequestMapper
{
    /**
     * @return array{listeCodesSecurite: list<string>}
     */
    public function toBody(SecurityCode ...$codes): array
    {
        $values = [];
        foreach ($codes as $code) {
            $values[] = $code->value();
        }

        return ['listeCodesSecurite' => $values];
    }
}
