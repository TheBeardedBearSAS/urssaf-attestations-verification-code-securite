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

/**
 * Splits the codes into chunks of {@see CertificateVerificationGateway::MAX_CODES_PER_CALL}, calls the gateway
 * sequentially and returns the results in input order. Duplicates are sent as given; an empty input makes no call.
 */
final readonly class VerifyCertificatesHandler
{
    public function __construct(private CertificateVerificationGateway $gateway) {}

    /**
     * @return list<VerificationResult>
     */
    public function __invoke(VerifyCertificates $query): array
    {
        $results = [];
        foreach (array_chunk($query->securityCodes, CertificateVerificationGateway::MAX_CODES_PER_CALL) as $chunk) {
            foreach ($this->gateway->verifyMany($query->tenantId, ...$chunk) as $result) {
                $results[] = $result;
            }
        }

        return $results;
    }
}
