<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\ServiceIds;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\VerificationDomain;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;

final class BundleConfigurationTest extends TestCase
{
    public function testTheDefaults(): void
    {
        $harness = Harness::boot();

        $configuration = $harness->privateService(ServiceIds::API_CONFIGURATION, ApiConfiguration::class);
        static::assertSame(Environment::Sandbox, $configuration->environment());
        static::assertSame('https://api-edi.urssaf.fr', $configuration->baseUri());
        static::assertSame(VerificationDomain::Urssaf, $configuration->domain());
        static::assertSame(60, $configuration->tokenSafetyMarginSeconds());
        static::assertSame(ApiConfiguration::DEFAULT_USER_AGENT, $configuration->userAgent());
    }

    public function testTheProductionEnvironmentAndTheOverridesAreApplied(): void
    {
        $harness = Harness::boot([
            'environment' => 'production',
            'base_uri' => 'https://proxy.example.test/',
            'domain' => 'ISU',
            'token_safety_margin' => 120,
        ]);

        $configuration = $harness->privateService(ServiceIds::API_CONFIGURATION, ApiConfiguration::class);
        static::assertSame(Environment::Production, $configuration->environment());
        static::assertSame('https://proxy.example.test', $configuration->baseUri());
        static::assertSame(VerificationDomain::Isu, $configuration->domain());
        static::assertSame(120, $configuration->tokenSafetyMarginSeconds());
    }

    public function testTheUrssafDomainCanBeExplicit(): void
    {
        $harness = Harness::boot(['domain' => 'URSSAF']);

        static::assertSame(VerificationDomain::Urssaf, $harness->privateService(ServiceIds::API_CONFIGURATION, ApiConfiguration::class)->domain());
    }

    public function testTheSafetyMarginCanBeZero(): void
    {
        $harness = Harness::boot(['token_safety_margin' => 0]);

        static::assertSame(0, $harness->privateService(ServiceIds::API_CONFIGURATION, ApiConfiguration::class)->tokenSafetyMarginSeconds());
    }

    public function testTheDomainParameterCanBeOmitted(): void
    {
        $harness = Harness::boot(['domain' => null]);

        static::assertNull($harness->privateService(ServiceIds::API_CONFIGURATION, ApiConfiguration::class)->domain());
    }

    public function testTheEnvironmentCanBeAnEnvironmentVariable(): void
    {
        $_ENV['URSSAF_TEST_ENV'] = 'production';
        try {
            $harness = Harness::boot(['environment' => '%env(URSSAF_TEST_ENV)%']);

            $configuration = $harness->privateService(ServiceIds::API_CONFIGURATION, ApiConfiguration::class);
            static::assertSame(Environment::Production, $configuration->environment());
        } finally {
            unset($_ENV['URSSAF_TEST_ENV']);
        }
    }

    public function testTheBundleExposesItsAliasAndDirectory(): void
    {
        $bundle = new UrssafAttestationsVerificationCodeSecuriteBundle();

        static::assertSame('urssaf_attestations_verification_code_securite', $bundle->getContainerExtension()?->getAlias());
        static::assertSame(\dirname(__DIR__, 3) . '/src/Bridge/Symfony', $bundle->getPath());
    }
}
