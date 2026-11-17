<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;

final readonly class VerifyCertificateHandler
{
    public function __construct(private CertificateVerificationGateway $gateway) {}

    public function __invoke(VerifyCertificate $query): VerificationResult
    {
        return $this->gateway->verify($query->tenantId, $query->securityCode);
    }
}
