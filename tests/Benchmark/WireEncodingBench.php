<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Benchmark;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonEncoder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerifyManyRequestMapper;

/**
 * Validation of the security codes and encoding of a full verification request (the largest batch accepted by one call),
 * down to the JSON string.
 */
final readonly class WireEncodingBench
{
    private VerifyManyRequestMapper $mapper;

    /** @var list<string> */
    private array $rawCodes;

    /** @var list<SecurityCode> */
    private array $codes;

    public function __construct()
    {
        $this->mapper = new VerifyManyRequestMapper();
        $this->rawCodes = array_map(
            static fn(int $index): string => \sprintf('KZHVYO7KP%06d', $index),
            range(1, CertificateVerificationGateway::MAX_CODES_PER_CALL),
        );
        $this->codes = array_map(SecurityCode::fromString(...), $this->rawCodes);
    }

    public function benchValidateAFullBatchOfSecurityCodes(): void
    {
        array_map(SecurityCode::fromString(...), $this->rawCodes);
    }

    public function benchEncodeAFullVerificationRequest(): void
    {
        JsonEncoder::encode($this->mapper->toBody(...$this->codes));
    }
}
