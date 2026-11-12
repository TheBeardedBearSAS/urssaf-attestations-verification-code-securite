<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UtcSystemClock;

#[CoversClass(UtcSystemClock::class)]
final class UtcSystemClockTest extends TestCase
{
    public function testReturnsTheCurrentTimeInUtc(): void
    {
        $before = time();
        $now = (new UtcSystemClock())->now();
        $after = time();

        static::assertSame('UTC', $now->getTimezone()->getName());
        static::assertGreaterThanOrEqual($before, $now->getTimestamp());
        static::assertLessThanOrEqual($after, $now->getTimestamp());
    }
}
