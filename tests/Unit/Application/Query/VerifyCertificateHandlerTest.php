<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Fake\FakeCertificateVerificationGateway;

#[CoversClass(VerifyCertificate::class)]
#[CoversClass(VerifyCertificateHandler::class)]
final class VerifyCertificateHandlerTest extends TestCase
{
    public function testReturnsTheGatewayResult(): void
    {
        $code = SecurityCode::fromString('ABC');
        $result = RejectedCode::of($code, 'FUNC202', 'Attestation inconnue');
        $gateway = new FakeCertificateVerificationGateway();
        $gateway->willVerify($result);

        $query = new VerifyCertificate(TenantId::fromString('tenant-1'), $code);

        static::assertSame($result, (new VerifyCertificateHandler($gateway))($query));
        static::assertSame([$code], $gateway->verifications());
        static::assertSame([], $gateway->batches());
        static::assertSame($code, $query->securityCode);
        static::assertSame('tenant-1', $query->tenantId->value());
    }

    public function testExceptionsPropagate(): void
    {
        $failure = new RuntimeException('boom');
        $gateway = new FakeCertificateVerificationGateway();
        $gateway->willVerify($failure);

        try {
            (new VerifyCertificateHandler($gateway))(
                new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString('ABC')),
            );
            static::fail('Expected exception');
        } catch (RuntimeException $exception) {
            static::assertSame($failure, $exception);
        }
    }
}
