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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\VerifyCertificateCommand;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\ServiceIds;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\App\TestKernel;

/**
 * The services as declared by the bundle, before the container is compiled.
 */
final class ServiceDefinitionsTest extends TestCase
{
    public function testTheDefaultHttpClientAndTokenStoreSettings(): void
    {
        $container = (new TestKernel(['http_client' => null]))->definitions();

        static::assertSame([['timeout' => 15.0]], $container->getDefinition(ServiceIds::SYMFONY_HTTP_CLIENT)->getArguments());
        static::assertEquals([new Reference('cache.app')], $container->getDefinition(TokenStore::class)->getArguments());
    }

    public function testTheDefaultHttpClientIsWrappedInAPsr18Client(): void
    {
        $container = (new TestKernel(['http_client' => null]))->definitions();

        static::assertEquals([new Reference(ServiceIds::SYMFONY_HTTP_CLIENT)], $container->getDefinition(ServiceIds::PSR18_CLIENT)->getArguments());
    }

    public function testTheGatewayLogsOnTheUrssafChannel(): void
    {
        $container = (new TestKernel())->definitions();

        $options = $container->getDefinition(ServiceIds::GATEWAY_OPTIONS);

        static::assertSame([['channel' => 'urssaf']], $options->getTag('monolog.logger'));
        static::assertEquals(new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE), $options->getArgument(1));
        static::assertSame([], $container->getDefinition(CertificateVerificationGateway::class)->getTag('monolog.logger'));
    }

    public function testTheCredentialsProviderServiceReplacesTheInMemoryProvider(): void
    {
        $container = (new TestKernel(['credentials' => null, 'credentials_provider' => 'test.credentials_provider']))->definitions();

        static::assertFalse($container->hasDefinition(CredentialsProvider::class));
        static::assertSame('test.credentials_provider', (string) $container->getAlias(CredentialsProvider::class));
    }

    public function testTheHttpTimeoutIsConfigurable(): void
    {
        $container = (new TestKernel(['http_timeout' => 2.5]))->definitions();

        static::assertSame([['timeout' => 2.5]], $container->getDefinition(ServiceIds::SYMFONY_HTTP_CLIENT)->getArguments());
    }

    public function testHostChecksAreAutoconfigured(): void
    {
        $container = (new TestKernel())->definitions();

        static::assertTrue($container->getDefinition('test.check')->hasTag('urssaf_attestations_verification_code_securite.installation_check'));
    }

    public function testTheVerifyCommandIsAutowiredTaggedAndGivenTheTenants(): void
    {
        $definition = (new TestKernel())->definitions()->getDefinition(VerifyCertificateCommand::class);

        static::assertTrue($definition->isAutowired());
        static::assertTrue($definition->hasTag('console.command'));
        static::assertSame('%urssaf_attestations_verification_code_securite.tenants%', $definition->getArgument('$configuredTenants'));
    }

    public function testTheSimulatedGatewayIsAPublicAliasAndNoHttpServiceIsDeclared(): void
    {
        $container = (new TestKernel(['gateway' => ['simulated' => true]]))->definitions();

        static::assertTrue($container->getAlias(SimulatedCertificateVerificationGateway::class)->isPublic());
        static::assertSame(
            CertificateVerificationGateway::class,
            (string) $container->getAlias(SimulatedCertificateVerificationGateway::class),
        );
        static::assertFalse($container->hasDefinition(ServiceIds::SYMFONY_HTTP_CLIENT));
        static::assertFalse($container->hasDefinition(ServiceIds::HTTP_TRANSPORT));
    }
}
