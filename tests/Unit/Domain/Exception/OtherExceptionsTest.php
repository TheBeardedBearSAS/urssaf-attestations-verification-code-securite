<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\RecordNotFound;

#[CoversClass(InvariantViolation::class)]
#[CoversClass(RecordNotFound::class)]
final class OtherExceptionsTest extends TestCase
{
    public function testInvariantViolation(): void
    {
        $exception = InvariantViolation::because('payment_request.deposit_too_high', 'Deposit too high.', ['max' => 50]);

        static::assertSame('payment_request.deposit_too_high', $exception->violationCode());
        static::assertSame('Deposit too high.', $exception->getMessage());
        static::assertSame(['max' => 50], $exception->params());
        static::assertSame([], InvariantViolation::because('a.b', 'm')->params());
    }

    public function testRecordNotFound(): void
    {
        $exception = RecordNotFound::withIdentifier('customer_registration', 'abc-1');

        static::assertSame('customer_registration.not_found', $exception->violationCode());
        static::assertSame('No customer_registration found for identifier "abc-1".', $exception->getMessage());
        static::assertSame('customer_registration', $exception->recordType());
        static::assertSame('abc-1', $exception->identifier());
        static::assertSame(['record_type' => 'customer_registration', 'identifier' => 'abc-1'], $exception->params());
    }
}
