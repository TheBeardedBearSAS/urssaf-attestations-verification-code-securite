<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Bridge\Symfony\DependencyInjection;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\GatewayOptionsFactory;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Clock\FrozenClock;

#[CoversClass(GatewayOptionsFactory::class)]
final class GatewayOptionsFactoryTest extends TestCase
{
    public function testTheContainerLoggerIsUsedWhenThereIsOne(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-17T10:00:00+00:00'));
        $logger = new NullLogger();

        $options = GatewayOptionsFactory::create($clock, $logger);

        static::assertSame($clock, $options->clock());
        static::assertSame($logger, $options->logger());
    }

    public function testNoLoggerIsConfiguredWhenTheContainerHasNone(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-17T10:00:00+00:00'));

        $options = GatewayOptionsFactory::create($clock, null);

        static::assertSame($clock, $options->clock());
        static::assertNull($options->logger());
    }
}
