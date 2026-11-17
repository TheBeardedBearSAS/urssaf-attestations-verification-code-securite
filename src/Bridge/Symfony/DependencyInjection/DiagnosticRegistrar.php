<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CheckInstallationCommand;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\AuthenticationCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CredentialsCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\InstallationCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\SimulatedGatewayCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\TokenStoreCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\TokenStore\InMemoryTokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\OAuth2TokenProvider;

/**
 * The "urssaf:check" command and its checks, in report order (registration order, host checks afterwards unless they
 * have a positive tag priority: a priority of 1 or more puts them before the checks of the package).
 *
 * @phpstan-import-type Config from ServiceRegistrar
 */
final readonly class DiagnosticRegistrar
{
    private const LOGGER_TAG = 'monolog.logger';
    private const LOGGER_CHANNEL = 'urssaf';
    private const SHARED_POOL = 'cache.app';
    /** Above the default priority of the checks of the host (0), so that they run before those of the host. */
    private const CHECK_PRIORITY = 1;

    public function __construct(private ServicesConfigurator $services) {}

    /**
     * @param Config $config
     */
    public function register(array $config): void
    {
        $this->check(CredentialsCheck::class, [new Reference(CredentialsProvider::class)]);
        $this->registerGatewayCheck($config);
        $this->check(TokenStoreCheck::class, [
            new Reference(TokenStore::class),
            new Reference(Clock::class),
            $config['token_store']['service'] === null && $config['token_store']['cache_pool'] === self::SHARED_POOL,
        ]);

        $this->services->set(CheckInstallationCommand::class)
            ->args([new TaggedIteratorArgument(InstallationCheck::TAG), '%' . UrssafAttestationsVerificationCodeSecuriteBundle::PARAMETER_TENANTS . '%'])
            ->tag('console.command');
    }

    /**
     * @param Config $config
     */
    private function registerGatewayCheck(array $config): void
    {
        if ($config['gateway']['simulated']) {
            $this->check(SimulatedGatewayCheck::class, []);

            return;
        }
        $this->registerProbeTokenProvider($config['http_client']);
        $this->check(AuthenticationCheck::class, [new Reference(CredentialsProvider::class), new Reference(ServiceIds::PROBE_TOKEN_PROVIDER)]);
    }

    /**
     * The OAuth2 token provider of the authentication check: an in-memory token store of its own, so that every check
     * asks URSSAF whatever the token store of the application.
     */
    private function registerProbeTokenProvider(?string $httpClient): void
    {
        $factories = new Reference(ServiceIds::PSR18_CLIENT);
        $this->services->set(ServiceIds::PROBE_TOKEN_PROVIDER, OAuth2TokenProvider::class)
            ->args([
                new Reference($httpClient ?? ServiceIds::PSR18_CLIENT),
                $factories,
                $factories,
                new Definition(InMemoryTokenStore::class),
                new Reference(Clock::class),
                new Reference(ServiceIds::API_CONFIGURATION),
                new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag(self::LOGGER_TAG, ['channel' => self::LOGGER_CHANNEL]);
    }

    /**
     * @param class-string<InstallationCheck> $class
     * @param list<mixed>                     $arguments
     */
    private function check(string $class, array $arguments): void
    {
        $this->services->set($class)->args($arguments)->tag(InstallationCheck::TAG, ['priority' => self::CHECK_PRIORITY]);
    }
}
