<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Contract;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\VerificationDomain;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerificationBuilder;

/**
 * Wires the real HTTP gateway against the URSSAF sandbox ("environnement Editeur") from environment variables:
 *
 * - URSSAF_SANDBOX_CLIENT_ID / URSSAF_SANDBOX_CLIENT_SECRET (required, otherwise the suite is skipped);
 * - URSSAF_SANDBOX_BASE_URI (optional host override);
 * - URSSAF_SANDBOX_VALID_SECURITY_CODE (optional, a code known to be valid in the sandbox);
 * - URSSAF_SANDBOX_UNKNOWN_SECURITY_CODE (optional, default "xxx": the unknown code example of the PDF, section 3.1.3).
 */
final readonly class SandboxGateway
{
    public const TENANT = 'contract-tests';
    public const DEFAULT_UNKNOWN_SECURITY_CODE = 'xxx';

    private function __construct(
        public CertificateVerificationGateway $withDomain,
        public CertificateVerificationGateway $withoutDomain,
        public TenantId $tenantId,
    ) {}

    /**
     * Null when the required environment variables are missing.
     */
    public static function fromEnvironment(): ?self
    {
        $clientId = self::env('URSSAF_SANDBOX_CLIENT_ID');
        $clientSecret = self::env('URSSAF_SANDBOX_CLIENT_SECRET');
        if ($clientId === null || $clientSecret === null) {
            return null;
        }

        $tenantId = TenantId::fromString(self::TENANT);
        $credentials = new InMemoryCredentialsProvider([$tenantId->value() => Credentials::of($clientId, $clientSecret)]);
        $factory = new Psr17Factory();
        $transport = new HttpTransport(new Psr18Client(HttpClient::create(['timeout' => 30]), $factory, $factory), $factory, $factory);
        $baseUri = self::env('URSSAF_SANDBOX_BASE_URI');

        return new self(
            self::gateway($transport, $credentials, new ApiConfiguration(Environment::Sandbox, $baseUri, VerificationDomain::Urssaf)),
            self::gateway($transport, $credentials, new ApiConfiguration(Environment::Sandbox, $baseUri, null)),
            $tenantId,
        );
    }

    public static function env(string $name): ?string
    {
        $value = getenv($name);

        return \is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public static function unknownSecurityCode(): SecurityCode
    {
        return SecurityCode::fromString(self::env('URSSAF_SANDBOX_UNKNOWN_SECURITY_CODE') ?? self::DEFAULT_UNKNOWN_SECURITY_CODE);
    }

    /**
     * Null when URSSAF_SANDBOX_VALID_SECURITY_CODE is not set.
     */
    public static function validSecurityCode(): ?SecurityCode
    {
        $code = self::env('URSSAF_SANDBOX_VALID_SECURITY_CODE');

        return $code === null ? null : SecurityCode::fromString($code);
    }

    private static function gateway(
        HttpTransport $transport,
        InMemoryCredentialsProvider $credentials,
        ApiConfiguration $configuration,
    ): CertificateVerificationGateway {
        return UrssafAttestationsVerificationBuilder::create($transport, $credentials, $configuration)->build()->gateway();
    }
}
