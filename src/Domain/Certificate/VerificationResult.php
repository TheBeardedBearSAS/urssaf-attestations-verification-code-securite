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
 * Outcome of the verification of one security code (sum type). The only implementations are
 * {@see VerifiedCertificate} and {@see RejectedCode}.
 */
interface VerificationResult
{
    public function securityCode(): SecurityCode;

    public function isValid(): bool;
}
