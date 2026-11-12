<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Testing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\IssuingBody;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;

#[CoversClass(SimulatedCertificateVerificationGateway::class)]
final class SimulatedCertificateVerificationGatewayTest extends TestCase
{
    public function testAnUnscriptedCodeIsRejectedAsUnknown(): void
    {
        $code = SecurityCode::fromString('UNKNOWN');

        $result = (new SimulatedCertificateVerificationGateway())->verify(TenantId::fromString('t1'), $code);

        static::assertInstanceOf(RejectedCode::class, $result);
        static::assertSame($code, $result->securityCode());
        static::assertSame('FUNC202', $result->faultCode());
        static::assertSame('Attestation inconnue', $result->faultName());
    }

    public function testACertifiedCodeIsAnsweredWithTheCertificate(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $certificate = self::certificate('ABC');
        $gateway->certify($certificate);

        // Matched by value, not by instance.
        $result = $gateway->verify(TenantId::fromString('t1'), SecurityCode::fromString(' ABC '));

        static::assertSame($certificate, $result);
    }

    public function testARejectedCodeIsAnsweredWithTheFault(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $gateway->reject(SecurityCode::fromString('OLD'), 'FUNC201', 'Attestation perimée depuis le 01/03/2019');

        $result = $gateway->verify(TenantId::fromString('t1'), SecurityCode::fromString('OLD'));

        static::assertInstanceOf(RejectedCode::class, $result);
        static::assertSame('OLD', $result->securityCode()->value());
        static::assertSame('FUNC201', $result->faultCode());
        static::assertSame('Attestation perimée depuis le 01/03/2019', $result->faultName());
    }

    public function testTheLastScriptingWinsForACode(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $code = SecurityCode::fromString('ABC');
        $gateway->reject($code, 'FUNC201', 'Attestation perimée depuis le 01/03/2019');
        $certificate = self::certificate('ABC');
        $gateway->certify($certificate);
        static::assertSame($certificate, $gateway->verify(TenantId::fromString('t1'), $code));

        $gateway->reject($code, 'FUNC201', 'Attestation perimée depuis le 01/03/2019');
        static::assertFalse($gateway->verify(TenantId::fromString('t1'), $code)->isValid());
    }

    public function testFailNextThrowsOnceOnVerify(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $failure = new RuntimeException('boom');
        $gateway->failNext(Operation::VerifyCertificate, $failure);
        $tenant = TenantId::fromString('t1');
        $code = SecurityCode::fromString('ABC');

        try {
            $gateway->verify($tenant, $code);
            static::fail('Expected the scripted failure.');
        } catch (RuntimeException $e) {
            static::assertSame($failure, $e);
        }

        static::assertFalse($gateway->verify($tenant, $code)->isValid());
        static::assertCount(2, $gateway->calls());
    }

    public function testFailNextThrowsOnceOnVerifyMany(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $failure = new RuntimeException('boom');
        $gateway->failNext(Operation::VerifyCertificates, $failure);
        $tenant = TenantId::fromString('t1');
        $code = SecurityCode::fromString('ABC');

        try {
            $gateway->verifyMany($tenant, $code);
            static::fail('Expected the scripted failure.');
        } catch (RuntimeException $e) {
            static::assertSame($failure, $e);
        }

        static::assertCount(1, $gateway->verifyMany($tenant, $code));
    }

    public function testFailuresAreQueuedPerOperationAndThrownInOrder(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $first = new RuntimeException('first');
        $second = new RuntimeException('second');
        $many = new RuntimeException('many');
        $gateway->failNext(Operation::VerifyCertificate, $first);
        $gateway->failNext(Operation::VerifyCertificate, $second);
        $gateway->failNext(Operation::VerifyCertificates, $many);
        $tenant = TenantId::fromString('t1');
        $code = SecurityCode::fromString('ABC');

        $thrown = [];
        try {
            $gateway->verifyMany($tenant, $code);
        } catch (RuntimeException $e) {
            $thrown[] = $e;
        }
        try {
            $gateway->verify($tenant, $code);
        } catch (RuntimeException $e) {
            $thrown[] = $e;
        }
        try {
            $gateway->verify($tenant, $code);
        } catch (RuntimeException $e) {
            $thrown[] = $e;
        }

        static::assertSame([$many, $first, $second], $thrown);
        static::assertFalse($gateway->verify($tenant, $code)->isValid());
        static::assertCount(1, $gateway->verifyMany($tenant, $code));
    }

    public function testCallsAreRecordedInOrder(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $t1 = TenantId::fromString('t1');
        $t2 = TenantId::fromString('t2');
        $a = SecurityCode::fromString('A');
        $b = SecurityCode::fromString('B');

        static::assertSame([], $gateway->calls());

        $gateway->verify($t1, $a);
        $gateway->verifyMany($t2, $a, $b);

        static::assertSame(
            [
                ['operation' => Operation::VerifyCertificate, 'tenantId' => $t1, 'securityCodes' => [$a]],
                ['operation' => Operation::VerifyCertificates, 'tenantId' => $t2, 'securityCodes' => [$a, $b]],
            ],
            $gateway->calls(),
        );
    }

    private static function certificate(string $code): VerifiedCertificate
    {
        return VerifiedCertificate::of(
            SecurityCode::fromString($code),
            'hash',
            'AVG_UR',
            'ACME',
            IssuingBody::of('837', 'Urssaf AUVERGNE'),
            null,
            [],
        );
    }
}
