<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Fake;

use Closure;
use LogicException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use Throwable;

/**
 * Scripted gateway: verify() answers with the next scripted response (a result, or a throwable to throw); verifyMany()
 * answers with the result of the scripted closure. Calls are journaled.
 */
final class FakeCertificateVerificationGateway implements CertificateVerificationGateway
{
    /** @var list<VerificationResult|Throwable> */
    private array $responses = [];

    /** @var null|Closure(list<SecurityCode>): (list<VerificationResult>|Throwable) */
    private ?Closure $batchVerifier = null;

    /** @var list<SecurityCode> */
    private array $verifications = [];

    /** @var list<list<SecurityCode>> */
    private array $batches = [];

    public function willVerify(VerificationResult|Throwable ...$responses): void
    {
        foreach ($responses as $response) {
            $this->responses[] = $response;
        }
    }

    /**
     * @param Closure(list<SecurityCode>): (list<VerificationResult>|Throwable) $batchVerifier
     */
    public function willVerifyManyWith(Closure $batchVerifier): void
    {
        $this->batchVerifier = $batchVerifier;
    }

    /**
     * @return list<SecurityCode>
     */
    public function verifications(): array
    {
        return $this->verifications;
    }

    /**
     * @return list<list<SecurityCode>>
     */
    public function batches(): array
    {
        return $this->batches;
    }

    public function verify(TenantId $tenantId, SecurityCode $securityCode): VerificationResult
    {
        $this->verifications[] = $securityCode;
        $response = array_shift($this->responses) ?? throw new LogicException('Unexpected verify() call.');
        if ($response instanceof Throwable) {
            throw $response;
        }

        return $response;
    }

    public function verifyMany(TenantId $tenantId, SecurityCode ...$securityCodes): array
    {
        $codes = array_values($securityCodes);
        $this->batches[] = $codes;
        $response = ($this->batchVerifier ?? throw new LogicException('Unexpected verifyMany() call.'))($codes);
        if ($response instanceof Throwable) {
            throw $response;
        }

        return $response;
    }
}
