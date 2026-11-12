<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf;

use Psr\Log\NullLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Id\RandomUuidGenerator;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Id\SecureRandomBytes;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\JsonApiClient;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\OAuth2TokenProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\CertificateDetailsMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerificationResultMapper;

/**
 * Public entry point to build the URSSAF HTTP gateway outside a framework bridge:
 *
 *     $gateway = UrssafGatewayFactory::createWith(
 *         new HttpTransport($psr18Client, $psr17RequestFactory, $psr17StreamFactory),
 *         $credentialsProvider,
 *         $tokenStore,
 *         ApiConfiguration::forEnvironment(Environment::Production),
 *         GatewayOptions::defaults()->withLogger($logger),
 *     );
 *
 * {@see self::create()} takes the same arguments without the options.
 *
 * The OAuth2 token provider and the JSON client it wires are internal.
 */
final class UrssafGatewayFactory
{
    /**
     * Gateway with the default options ({@see GatewayOptions::defaults()}).
     */
    public static function create(
        HttpTransport $transport,
        CredentialsProvider $credentials,
        TokenStore $tokens,
        ApiConfiguration $configuration,
    ): UrssafHttpGateway {
        return self::createWith($transport, $credentials, $tokens, $configuration, GatewayOptions::defaults());
    }

    public static function createWith(
        HttpTransport $transport,
        CredentialsProvider $credentials,
        TokenStore $tokens,
        ApiConfiguration $configuration,
        GatewayOptions $options,
    ): UrssafHttpGateway {
        $clock = $options->clock() ?? new UtcSystemClock();
        $logger = $options->logger() ?? new NullLogger();
        $tokenProvider = new OAuth2TokenProvider(
            $transport->client(),
            $transport->requestFactory(),
            $transport->streamFactory(),
            $tokens,
            $clock,
            $configuration,
            $logger,
        );
        $client = new JsonApiClient(
            $transport->client(),
            $transport->requestFactory(),
            $transport->streamFactory(),
            $tokenProvider,
            $options->idGenerator() ?? new RandomUuidGenerator(new SecureRandomBytes()),
            $clock,
            $configuration,
            $logger,
        );

        return new UrssafHttpGateway(
            $credentials,
            $client,
            $configuration,
            new VerificationResultMapper(new CertificateDetailsMapper($logger)),
        );
    }
}
