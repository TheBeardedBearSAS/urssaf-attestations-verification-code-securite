<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

#[CoversClass(TenantId::class)]
final class TenantIdTest extends TestCase
{
    public function testValuesAreKeptVerbatim(): void
    {
        foreach (['a', ' padded value ', str_repeat('x', TenantId::MAX_LENGTH), str_repeat('é', TenantId::MAX_LENGTH)] as $value) {
            $identifier = TenantId::fromString($value);
            static::assertSame($value, $identifier->value());
            static::assertSame($value, (string) $identifier);
        }
    }

    public function testBlankValuesAreRejected(): void
    {
        foreach (['', ' ', "\t\n"] as $value) {
            try {
                TenantId::fromString($value);
                static::fail('Expected exception');
            } catch (InvalidValue $exception) {
                static::assertSame('tenant_id.empty', $exception->violationCode());
            }
        }
    }

    public function testTooLongValuesAreRejected(): void
    {
        try {
            TenantId::fromString(str_repeat('x', TenantId::MAX_LENGTH + 1));
            static::fail('Expected exception');
        } catch (InvalidValue $exception) {
            static::assertSame('tenant_id.too_long', $exception->violationCode());
            static::assertSame(['max_length' => 64], $exception->params());
        }
    }

    public function testEquality(): void
    {
        static::assertTrue(TenantId::fromString('acme')->equals(TenantId::fromString('acme')));
        static::assertFalse(TenantId::fromString('acme')->equals(TenantId::fromString('Acme')));
    }
}
