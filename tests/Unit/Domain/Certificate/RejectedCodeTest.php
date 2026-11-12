<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Certificate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\FaultCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\IssuingBody;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;

#[CoversClass(RejectedCode::class)]
#[CoversClass(IssuingBody::class)]
final class RejectedCodeTest extends TestCase
{
    public function testKnownFault(): void
    {
        $code = SecurityCode::fromString('ABC');
        $result = RejectedCode::of($code, 'FUNC201', 'Attestation perimee');

        static::assertSame($code, $result->securityCode());
        static::assertSame('FUNC201', $result->faultCode());
        static::assertSame(FaultCode::Expired, $result->fault());
        static::assertSame('Attestation perimee', $result->faultName());
        static::assertFalse($result->isValid());
    }

    public function testUnknownAndMissingFault(): void
    {
        $code = SecurityCode::fromString('ABC');

        static::assertNull(RejectedCode::of($code, 'FUNC999', null)->fault());
        static::assertSame('FUNC999', RejectedCode::of($code, 'FUNC999', null)->faultCode());
        static::assertNull(RejectedCode::of($code, 'FUNC999', null)->faultName());
        static::assertNull(RejectedCode::of($code, null, null)->fault());
        static::assertNull(RejectedCode::of($code, null, null)->faultCode());
    }

    public function testIssuingBody(): void
    {
        $body = IssuingBody::of('837', 'Urssaf AUVERGNE');
        static::assertSame('837', $body->code());
        static::assertSame('Urssaf AUVERGNE', $body->label());
        static::assertNull(IssuingBody::of(null, null)->code());
        static::assertNull(IssuingBody::of(null, null)->label());
    }
}
