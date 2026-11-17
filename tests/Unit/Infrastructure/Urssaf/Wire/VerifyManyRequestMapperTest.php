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
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonEncoder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerifyManyRequestMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

#[CoversClass(VerifyManyRequestMapper::class)]
final class VerifyManyRequestMapperTest extends TestCase
{
    public function testItMatchesTheOfficialRequestExample(): void
    {
        $codes = array_map(SecurityCode::fromString(...), [
            'KZHVYO7KP3JGXNL',
            'R9ES9TEO7IZNIRA',
            'AMOHWO6UPE8MQL5',
            'VZY816V353A73KE',
            'XTROQW5FLMBNZWB',
            '2WKNZFOUTWUWM56',
            'OPP1QIM6MB5SRLW',
            '5B1UXK5U3P4OWKX',
            '4W6UKDR41G457CW',
        ]);

        $body = JsonEncoder::encode((new VerifyManyRequestMapper())->toBody(...$codes));

        static::assertJsonStringEqualsJsonString(Fixtures::load('verify_many_request'), $body);
    }

    public function testItKeepsOrderAndDuplicates(): void
    {
        $body = (new VerifyManyRequestMapper())->toBody(
            SecurityCode::fromString('B'),
            SecurityCode::fromString('A'),
            SecurityCode::fromString('B'),
        );

        static::assertSame(['listeCodesSecurite' => ['B', 'A', 'B']], $body);
    }

    public function testItEncodesNoCodeAsAnEmptyList(): void
    {
        static::assertSame(['listeCodesSecurite' => []], (new VerifyManyRequestMapper())->toBody());
    }
}
