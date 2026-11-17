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
final class PayloadTest extends TestCase
{
    public function testItDistinguishesListsFromObjects(): void
    {
        static::assertTrue(JsonDecoder::decode('[{"a":1}]')->isList());
        static::assertTrue(JsonDecoder::decode('[]')->isList());
        static::assertFalse(JsonDecoder::decode('{"a":1}')->isList());
    }

    public function testStringReturnsOnlyStrings(): void
    {
        $payload = JsonDecoder::decode('{"s":"x","i":1,"n":null,"a":[],"b":true}');

        static::assertSame('x', $payload->string('s'));
        static::assertNull($payload->string('i'));
        static::assertNull($payload->string('n'));
        static::assertNull($payload->string('a'));
        static::assertNull($payload->string('b'));
        static::assertNull($payload->string('missing'));
    }

    public function testRequireStringReturnsANonEmptyString(): void
    {
        static::assertSame('x', JsonDecoder::decode('{"s":"x"}')->requireString('s'));
    }

    public function testRequireStringRejectsMissingEmptyAndNonStringValues(): void
    {
        foreach (['{}', '{"s":""}', '{"s":1}'] as $json) {
            try {
                JsonDecoder::decode($json)->requireString('s');
                static::fail('Expected an UnexpectedUrssafResponse for ' . $json);
            } catch (UnexpectedUrssafResponse $exception) {
                static::assertSame('Unexpected URSSAF response: field "s" is missing or empty.', $exception->getMessage());
            }
        }
    }

    public function testFromDecodedRejectsScalars(): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage('Unexpected URSSAF response: body is neither a JSON object nor a JSON array.');

        Payload::fromDecoded('text');
    }
}
