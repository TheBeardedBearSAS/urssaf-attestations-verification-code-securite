<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonDecoder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\Payload;

#[CoversClass(Payload::class)]
final class PayloadNumberTest extends TestCase
{
    public function testNumberReturnsIntegersAndFloats(): void
    {
        $payload = JsonDecoder::decode('{"i":2000,"f":11.02,"n":null}');

        static::assertSame(2000, $payload->number('i'));
        static::assertSame(11.02, $payload->number('f'));
        static::assertNull($payload->number('n'));
        static::assertNull($payload->number('missing'));
    }

    public function testNumberRejectsOtherTypes(): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage('field "s" is not a number');

        JsonDecoder::decode('{"s":"12"}')->number('s');
    }
}
