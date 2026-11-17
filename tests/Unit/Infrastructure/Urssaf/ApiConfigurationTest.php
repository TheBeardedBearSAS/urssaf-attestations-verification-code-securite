<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafEndpoints;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\VerificationDomain;

#[CoversClass(ApiConfiguration::class)]
final class ApiConfigurationTest extends TestCase
{
    /**
     * @return iterable<string, array{Environment, string}>
     */
    public static function environments(): iterable
    {
        yield 'sandbox' => [Environment::Sandbox, 'https://api-edi.urssaf.fr'];
        yield 'production' => [Environment::Production, 'https://api.urssaf.fr'];
    }

    #[DataProvider('environments')]
    public function testItResolvesTheEnvironmentHost(Environment $environment, string $expected): void
    {
        $configuration = ApiConfiguration::forEnvironment($environment);

        static::assertSame($expected, $configuration->baseUri());
        static::assertSame($environment, $configuration->environment());
    }

    public function testItBuildsTokenAndApiUris(): void
    {
        $configuration = ApiConfiguration::forEnvironment(Environment::Production);

        static::assertSame('https://api.urssaf.fr/api/oauth/v1/token', $configuration->tokenUri());
        static::assertSame(
            'https://api.urssaf.fr/attn/verification/v1/codes_securite/verifier',
            $configuration->apiUri(UrssafEndpoints::VERIFY_PATH),
        );
    }

    public function testItAppliesTheBaseUriOverrideWithoutTrailingSlash(): void
    {
        $configuration = new ApiConfiguration(Environment::Sandbox, 'https://proxy.example.test/', VerificationDomain::Urssaf);

        static::assertSame('https://proxy.example.test', $configuration->baseUri());
        static::assertSame('https://proxy.example.test/api/oauth/v1/token', $configuration->tokenUri());
    }

    public function testItHasSensibleDefaults(): void
    {
        $configuration = ApiConfiguration::forEnvironment(Environment::Sandbox);

        static::assertSame(VerificationDomain::Urssaf, $configuration->domain());
        static::assertSame(60, $configuration->tokenSafetyMarginSeconds());
        static::assertSame('thebeardedbear-urssaf-attestations-verification-code-securite', $configuration->userAgent());
    }

    public function testItExposesCustomSettings(): void
    {
        $configuration = new ApiConfiguration(Environment::Sandbox, null, VerificationDomain::Isu, 5, 'my-agent');

        static::assertSame(VerificationDomain::Isu, $configuration->domain());
        static::assertSame(5, $configuration->tokenSafetyMarginSeconds());
        static::assertSame('my-agent', $configuration->userAgent());
    }

    public function testItAllowsOmittingTheDomain(): void
    {
        static::assertNull((new ApiConfiguration(Environment::Sandbox, null, null))->domain());
    }

    public function testItAcceptsAZeroSafetyMargin(): void
    {
        static::assertSame(0, (new ApiConfiguration(Environment::Sandbox, null, null, 0))->tokenSafetyMarginSeconds());
    }

    public function testItRejectsANegativeSafetyMargin(): void
    {
        try {
            new ApiConfiguration(Environment::Sandbox, null, null, -1);
            static::fail('A negative safety margin must be rejected.');
        } catch (InvalidValue $invalidValue) {
            static::assertSame('token_safety_margin.negative', $invalidValue->violationCode());
            static::assertSame('Invalid token_safety_margin: the value must not be negative.', $invalidValue->getMessage());
        }
    }
}
