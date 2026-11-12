<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf;

use Psr\Log\LoggerInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\IdGenerator;

/**
 * Optional collaborators of the gateway (see {@see UrssafGatewayFactory}), immutable; each one not given keeps its
 * default:
 * - id generator: random UUIDs (x-request-id);
 * - clock: the system clock (UTC);
 * - logger: no logging.
 *
 * There is no rate limiter option: URSSAF publishes no quota value for this API (ADR 0004).
 *
 *     GatewayOptions::defaults()->withLogger($logger)
 */
final readonly class GatewayOptions
{
    private function __construct(
        private ?IdGenerator $idGenerator,
        private ?Clock $clock,
        private ?LoggerInterface $logger,
    ) {}

    public static function defaults(): self
    {
        return new self(null, null, null);
    }

    public function withIdGenerator(IdGenerator $idGenerator): self
    {
        return new self($idGenerator, $this->clock, $this->logger);
    }

    public function withClock(Clock $clock): self
    {
        return new self($this->idGenerator, $clock, $this->logger);
    }

    public function withLogger(LoggerInterface $logger): self
    {
        return new self($this->idGenerator, $this->clock, $logger);
    }

    /**
     * Null when not given (the factory default applies).
     */
    public function idGenerator(): ?IdGenerator
    {
        return $this->idGenerator;
    }

    /**
     * Null when not given (the factory default applies).
     */
    public function clock(): ?Clock
    {
        return $this->clock;
    }

    /**
     * Null when not given (the factory default applies).
     */
    public function logger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
