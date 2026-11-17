<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Clock;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Clock\SystemClock;

#[CoversClass(SystemClock::class)]
final class SystemClockTest extends TestCase
{
    public function testDelegatesToThePsrClock(): void
    {
        $psrClock = new MockClock('2026-01-01T10:00:00+00:00');
        $clock = new SystemClock($psrClock);

        static::assertSame('2026-01-01T10:00:00+00:00', $clock->now()->format(DATE_ATOM));

        $psrClock->modify('+1 hour');

        static::assertSame('2026-01-01T11:00:00+00:00', $clock->now()->format(DATE_ATOM));
    }
}
