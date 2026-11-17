<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Port\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\ApiError;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\ApiErrors;

#[CoversClass(ApiError::class)]
#[CoversClass(ApiErrors::class)]
final class ApiErrorsTest extends TestCase
{
    public function testApiError(): void
    {
        $error = ApiError::of('FUNC015', 'Le code de sécurité est obligatoire', 'Détail');

        static::assertSame('FUNC015', $error->code());
        static::assertSame('Le code de sécurité est obligatoire', $error->message());
        static::assertSame('Détail', $error->description());
        static::assertTrue($error->hasCode('FUNC015'));
        static::assertFalse($error->hasCode('FUNC013'));
        static::assertTrue($error->equals(ApiError::of('FUNC015', 'Le code de sécurité est obligatoire', 'Détail')));
        static::assertFalse($error->equals(ApiError::of('X', 'Le code de sécurité est obligatoire', 'Détail')));
        static::assertFalse($error->equals(ApiError::of('FUNC015', 'X', 'Détail')));
        static::assertFalse($error->equals(ApiError::of('FUNC015', 'Le code de sécurité est obligatoire', 'X')));
    }

    public function testCollection(): void
    {
        $first = ApiError::of('FUNC013', 'm', 'd');
        $second = ApiError::of('FUNC015', 'm', 'd');

        $errors = ApiErrors::of($first, $second);

        static::assertFalse($errors->isEmpty());
        static::assertCount(2, $errors);
        static::assertSame([$first, $second], $errors->all());
        static::assertSame(['FUNC013', 'FUNC015'], $errors->codes());
        static::assertTrue($errors->hasCode('FUNC015'));
        static::assertTrue($errors->hasCode('FUNC013'));
        static::assertFalse($errors->hasCode('ERR_TECHNIQUE'));
    }

    public function testEmptyCollections(): void
    {
        static::assertTrue(ApiErrors::none()->isEmpty());
        static::assertCount(0, ApiErrors::none());
        static::assertSame([], ApiErrors::none()->all());
        static::assertTrue(ApiErrors::of()->isEmpty());
        static::assertFalse(ApiErrors::none()->hasCode('X'));
    }

    public function testOfNormalisesKeys(): void
    {
        $error = ApiError::of('X', 'm', 'd');

        static::assertSame([$error], ApiErrors::of(...['named' => $error])->all());
    }
}
