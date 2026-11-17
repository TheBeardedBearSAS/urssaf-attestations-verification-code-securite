<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Certificate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\CertificateNature;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\EpmDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\Establishment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\IssuingBody;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\CalendarDate;

#[CoversClass(VerifiedCertificate::class)]
final class VerifiedCertificateTest extends TestCase
{
    public function testAccessors(): void
    {
        $code = SecurityCode::fromString('ABC');
        $body = IssuingBody::of('837', 'Urssaf');
        $date = CalendarDate::fromString('2020-10-02');
        $details = [EpmDetails::of([Establishment::of('1', 'a')])];
        $result = VerifiedCertificate::of($code, 'hash', 'AVG_UR', 'ACME', $body, $date, $details);

        static::assertSame($code, $result->securityCode());
        static::assertSame('hash', $result->hashKey());
        static::assertSame('AVG_UR', $result->natureCode());
        static::assertSame(CertificateNature::VigilanceUrssaf, $result->nature());
        static::assertSame('ACME', $result->denomination());
        static::assertSame($body, $result->issuingBody());
        static::assertSame($date, $result->issuedOn());
        static::assertSame($details, $result->details());
        static::assertTrue($result->isValid());
    }

    public function testNatureViewIsNullWhenUnknownOrAbsent(): void
    {
        $code = SecurityCode::fromString('ABC');
        $body = IssuingBody::of(null, null);

        $unknown = VerifiedCertificate::of($code, null, 'ZZZ', null, $body, null, []);
        static::assertSame('ZZZ', $unknown->natureCode());
        static::assertNull($unknown->nature());

        $absent = VerifiedCertificate::of($code, null, null, null, $body, null, []);
        static::assertNull($absent->natureCode());
        static::assertNull($absent->nature());
        static::assertNull($absent->hashKey());
        static::assertNull($absent->denomination());
        static::assertNull($absent->issuedOn());
        static::assertSame([], $absent->details());
    }

    public function testDebugInfoMasksDenomination(): void
    {
        $code = SecurityCode::fromString('ABC');
        $body = IssuingBody::of('837', 'Urssaf');
        $date = CalendarDate::fromString('2020-10-02');
        $details = [EpmDetails::of([])];

        static::assertSame(
            [
                'securityCode' => $code,
                'hashKey' => 'hash',
                'natureCode' => 'AVG_UR',
                'denomination' => '***',
                'issuingBody' => $body,
                'issuedOn' => $date,
                'details' => $details,
            ],
            VerifiedCertificate::of($code, 'hash', 'AVG_UR', 'ACME', $body, $date, $details)->__debugInfo(),
        );
        static::assertNull(VerifiedCertificate::of($code, null, null, null, $body, null, [])->__debugInfo()['denomination']);
    }
}
