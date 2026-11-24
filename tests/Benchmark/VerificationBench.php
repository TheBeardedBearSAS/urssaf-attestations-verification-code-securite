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

use Nyholm\Psr7\Factory\Psr17Factory;
use RuntimeException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerification;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerificationBuilder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

/**
 * Full verification through the standalone entry point and the HTTP gateway, over an in-memory PSR-18 client: request
 * building, authentication with a cached token, response decoding and mapping. No network is involved.
 */
final readonly class VerificationBench
{
    private const VALID_CODE = '42TROLLQU3DT999';

    private UrssafAttestationsVerification $package;

    private VerifyCertificate $one;

    private VerifyCertificates $batch;

    public function __construct()
    {
        $codes = array_map(
            static fn(int $index): SecurityCode => SecurityCode::fromString(\sprintf('KZHVYO7KP%06d', $index)),
            range(1, CertificateVerificationGateway::MAX_CODES_PER_CALL),
        );
        $factory = new Psr17Factory();
        $client = new StaticResponseClient($factory, [
            'GET' => Fixtures::load('verify_response_valid'),
            'POST' => self::batchResponse($codes),
        ], Fixtures::load('token_response'));
        $this->package = UrssafAttestationsVerificationBuilder::create(
            new HttpTransport($client, $factory, $factory),
            new InMemoryCredentialsProvider(['tenant-1' => Credentials::of('client-id', 'client-secret')]),
            ApiConfiguration::forEnvironment(Environment::Production),
        )->build();
        $tenant = TenantId::fromString('tenant-1');
        $this->one = new VerifyCertificate($tenant, SecurityCode::fromString(self::VALID_CODE));
        $this->batch = new VerifyCertificates($tenant, ...$codes);
        // Fetches the access token once: the benchmarks measure the calls made with a cached token.
        $this->benchVerifyOneCertificate();
    }

    public function benchVerifyOneCertificate(): void
    {
        $this->package->verifyCertificate()($this->one);
    }

    public function benchVerifyAFullBatchOfCertificates(): void
    {
        $this->package->verifyCertificates()($this->batch);
    }

    /**
     * The answer to a batch call: the valid certificate of the fixture, once per requested code.
     *
     * @param list<SecurityCode> $codes
     */
    private static function batchResponse(array $codes): string
    {
        $fixture = Fixtures::load('verify_response_valid');
        $start = strpos($fixture, '[');
        $end = strrpos($fixture, ']');
        if ($start === false || $end === false) {
            throw new RuntimeException('Unexpected fixture verify_response_valid');
        }
        $item = substr($fixture, $start + 1, $end - $start - 1);
        $items = array_map(static fn(SecurityCode $code): string => str_replace(self::VALID_CODE, $code->value(), $item), $codes);

        return substr($fixture, 0, $start + 1) . implode(',', $items) . substr($fixture, $end);
    }
}
