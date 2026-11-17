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

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\ErrorPayloadParser;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonDecoder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerificationResultMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

/**
 * Decoding of the official URSSAF responses, from the raw JSON string: a valid certificate with its details, an
 * unknown code, and an error payload.
 */
final readonly class WireDecodingBench
{
    private VerificationResultMapper $mapper;

    private ErrorPayloadParser $errorParser;

    private string $validResponse;

    private string $unknownResponse;

    private string $errorResponse;

    public function __construct()
    {
        $this->mapper = new VerificationResultMapper();
        $this->errorParser = new ErrorPayloadParser();
        $this->validResponse = Fixtures::load('verify_response_valid');
        $this->unknownResponse = Fixtures::load('verify_response_unknown');
        $this->errorResponse = Fixtures::load('error_too_many_codes');
    }

    public function benchDecodeAValidCertificate(): void
    {
        $this->mapper->map(JsonDecoder::decode($this->validResponse));
    }

    public function benchDecodeAnUnknownCode(): void
    {
        $this->mapper->map(JsonDecoder::decode($this->unknownResponse));
    }

    public function benchParseAnErrorPayload(): void
    {
        $this->errorParser->parseBody($this->errorResponse);
    }
}
