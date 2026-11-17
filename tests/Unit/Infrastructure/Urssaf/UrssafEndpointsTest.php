<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafEndpoints;

#[CoversNothing]
final class UrssafEndpointsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function constants(): iterable
    {
        yield 'token path' => [UrssafEndpoints::TOKEN_PATH, '/api/oauth/v1/token'];
        yield 'token scope' => [UrssafEndpoints::TOKEN_SCOPE, 'attn.codes_securite'];
        yield 'api base path' => [UrssafEndpoints::API_BASE_PATH, '/attn/verification/v1'];
        yield 'verify path' => [UrssafEndpoints::VERIFY_PATH, '/codes_securite/verifier'];
    }

    #[DataProvider('constants')]
    public function testItDeclaresTheDocumentedValues(string $actual, string $expected): void
    {
        static::assertSame($expected, $actual);
    }
}
