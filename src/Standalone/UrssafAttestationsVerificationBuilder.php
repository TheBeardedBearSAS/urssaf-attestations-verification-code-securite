<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone;

use Psr\Log\LoggerInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\TokenStore\InMemoryTokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\GatewayOptions;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafGatewayFactory;

/**
 * Wires the package without a framework. Every "with" method returns a new builder.
 *
 * Defaults: in-memory token store (use a store shared by every process in production), the UTC system clock, no
 * logging.
 */
final readonly class UrssafAttestationsVerificationBuilder
{
    private function __construct(
        private HttpTransport $transport,
        private CredentialsProvider $credentials,
        private ApiConfiguration $configuration,
        private TokenStore $tokenStore,
        private GatewayOptions $options,
        private ?CertificateVerificationGateway $gateway,
    ) {}

    public static function create(
        HttpTransport $transport,
        CredentialsProvider $credentials,
        ApiConfiguration $configuration,
    ): self {
        return new self($transport, $credentials, $configuration, new InMemoryTokenStore(), GatewayOptions::defaults(), null);
    }

    /**
     * A token store shared by every process that calls URSSAF (PSR-6 / PSR-16 store).
     */
    public function withTokenStore(TokenStore $tokenStore): self
    {
        return new self($this->transport, $this->credentials, $this->configuration, $tokenStore, $this->options, $this->gateway);
    }

    /**
     * The logger of the HTTP gateway.
     */
    public function withLogger(LoggerInterface $logger): self
    {
        return new self(
            $this->transport,
            $this->credentials,
            $this->configuration,
            $this->tokenStore,
            $this->options->withLogger($logger),
            $this->gateway,
        );
    }

    public function withClock(Clock $clock): self
    {
        return new self(
            $this->transport,
            $this->credentials,
            $this->configuration,
            $this->tokenStore,
            $this->options->withClock($clock),
            $this->gateway,
        );
    }

    /**
     * Replaces the HTTP gateway (for instance with the Testing\SimulatedCertificateVerificationGateway in tests): the
     * HTTP gateway is then not built.
     */
    public function withGateway(CertificateVerificationGateway $gateway): self
    {
        return new self($this->transport, $this->credentials, $this->configuration, $this->tokenStore, $this->options, $gateway);
    }

    public function build(): UrssafAttestationsVerification
    {
        return new UrssafAttestationsVerification(
            $this->gateway ?? UrssafGatewayFactory::createWith(
                $this->transport,
                $this->credentials,
                $this->tokenStore,
                $this->configuration,
                $this->options,
            ),
        );
    }
}
