<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\ClockFactory;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Clock\SystemClock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UtcSystemClock;

final class ClockFactoryTest extends TestCase
{
    public function testTheClockOfTheApplicationIsUsedWhenThereIsOne(): void
    {
        $clock = ClockFactory::create(new MockClock('2022-03-01 10:00:00 UTC'));

        static::assertInstanceOf(SystemClock::class, $clock);
        static::assertSame('2022-03-01T10:00:00+00:00', $clock->now()->format(DATE_ATOM));
    }

    public function testTheUtcSystemClockIsTheFallback(): void
    {
        static::assertInstanceOf(UtcSystemClock::class, ClockFactory::create(null));
    }
}
