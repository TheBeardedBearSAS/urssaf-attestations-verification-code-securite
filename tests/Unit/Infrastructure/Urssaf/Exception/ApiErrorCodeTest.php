<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\ApiErrorCode;

#[CoversClass(ApiErrorCode::class)]
final class ApiErrorCodeTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rawCodes(): iterable
    {
        yield 'clean' => ['FUNC013', 'FUNC013'];
        yield 'surrounding spaces' => [' FUNC015 ', 'FUNC015'];
        yield 'inner space' => ['ERR_ TECHNIQUE', 'ERR_TECHNIQUE'];
        yield 'tabs and newlines' => ["\tFUNC\n016\r", 'FUNC016'];
        yield 'empty' => ['', ''];
    }

    #[DataProvider('rawCodes')]
    public function testItNormalisesWhitespace(string $raw, string $expected): void
    {
        static::assertSame($expected, ApiErrorCode::normalise($raw));
    }

    public function testWireValues(): void
    {
        static::assertSame('FUNC013', ApiErrorCode::ServiceCallFailed->value);
        static::assertSame('FUNC015', ApiErrorCode::SecurityCodeRequired->value);
        static::assertSame('FUNC016', ApiErrorCode::TooManySecurityCodes->value);
        static::assertSame('ERR_TECHNIQUE', ApiErrorCode::TechnicalError->value);
        static::assertCount(4, ApiErrorCode::cases());
    }

    public function testFromCodeResolvesDirtyCodes(): void
    {
        static::assertSame(ApiErrorCode::SecurityCodeRequired, ApiErrorCode::fromCode(' FUNC 015 '));
        static::assertSame(ApiErrorCode::TechnicalError, ApiErrorCode::fromCode('ERR_TECHNIQUE'));
    }

    public function testFromCodeReturnsNullForUnknownCodes(): void
    {
        static::assertNull(ApiErrorCode::fromCode('FUNC201'));
    }

    public function testOnlyTheTechnicalErrorIsTechnical(): void
    {
        $technical = array_filter(ApiErrorCode::cases(), static fn(ApiErrorCode $code): bool => $code->isTechnical());

        static::assertSame([ApiErrorCode::TechnicalError], array_values($technical));
    }
}
