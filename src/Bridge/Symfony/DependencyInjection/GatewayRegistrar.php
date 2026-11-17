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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\GatewayOptions;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafGatewayFactory;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;

/**
 * The URSSAF HTTP gateway and everything it needs: PSR-18 client and PSR-17 factories.
 *
 * @phpstan-import-type Config from ServiceRegistrar
 */
final readonly class GatewayRegistrar
{
    private const LOGGER_TAG = 'monolog.logger';
    private const LOGGER_CHANNEL = 'urssaf';

    public function __construct(private ServicesConfigurator $services) {}

    /**
     * @param Config $config
     */
    public function register(array $config): void
    {
        if ($config['gateway']['simulated']) {
            $this->services->set(CertificateVerificationGateway::class, SimulatedCertificateVerificationGateway::class);
            $this->services->alias(SimulatedCertificateVerificationGateway::class, CertificateVerificationGateway::class)->public();

            return;
        }
        $this->services->set(ServiceIds::SYMFONY_HTTP_CLIENT, HttpClientInterface::class)
            ->factory(HttpClient::class . '::create')
            ->args([['timeout' => $config['http_timeout']]]);
        // Also provides the PSR-17 request and stream factories, whatever the PSR-18 client in use.
        $this->services->set(ServiceIds::PSR18_CLIENT, Psr18Client::class)->args([new Reference(ServiceIds::SYMFONY_HTTP_CLIENT)]);
        $this->services->set(ServiceIds::HTTP_TRANSPORT, HttpTransport::class)->args([
            new Reference($config['http_client'] ?? ServiceIds::PSR18_CLIENT),
            new Reference(ServiceIds::PSR18_CLIENT),
            new Reference(ServiceIds::PSR18_CLIENT),
        ]);
        $this->services->set(ServiceIds::GATEWAY_OPTIONS, GatewayOptions::class)
            ->factory(GatewayOptionsFactory::class . '::create')
            ->args([new Reference(Clock::class), new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
            // The logger is an argument of these options, not of the gateway: the channel tag must sit on this definition.
            ->tag(self::LOGGER_TAG, ['channel' => self::LOGGER_CHANNEL]);
        $this->services->set(CertificateVerificationGateway::class, UrssafHttpGateway::class)
            ->factory(UrssafGatewayFactory::class . '::createWith')
            ->args([
                new Reference(ServiceIds::HTTP_TRANSPORT),
                new Reference(CredentialsProvider::class),
                new Reference(TokenStore::class),
                new Reference(ServiceIds::API_CONFIGURATION),
                new Reference(ServiceIds::GATEWAY_OPTIONS),
            ]);
    }
}
