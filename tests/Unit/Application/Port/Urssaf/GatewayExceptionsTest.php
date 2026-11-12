<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Port\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\ApiError;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\ApiErrors;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafAuthenticationFailed;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafRequestRejected;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;

#[CoversClass(UrssafRequestRejected::class)]
#[CoversClass(UrssafAuthenticationFailed::class)]
#[CoversClass(UrssafUnavailable::class)]
final class GatewayExceptionsTest extends TestCase
{
    public function testRequestRejected(): void
    {
        $errors = ApiErrors::of(ApiError::of('FUNC013', 'm', 'd'), ApiError::of('FUNC015', 'm', 'd'));

        $exception = UrssafRequestRejected::withErrors(Operation::VerifyCertificates, 400, $errors);

        static::assertSame(
            'URSSAF operation verifierListeCodesSecuriteAttestationGen rejected the request (HTTP 400): FUNC013, FUNC015.',
            $exception->getMessage(),
        );
        static::assertSame(Operation::VerifyCertificates, $exception->operation());
        static::assertSame(400, $exception->httpStatus());
        static::assertSame(400, $exception->getCode());
        static::assertSame($errors, $exception->errors());
        static::assertTrue($exception->hasErrorCode('FUNC015'));
        static::assertFalse($exception->hasErrorCode('FUNC016'));
    }

    public function testRequestRejectedWithoutErrors(): void
    {
        $exception = UrssafRequestRejected::withErrors(Operation::VerifyCertificate, 400, ApiErrors::none());

        static::assertSame(
            'URSSAF operation verifierCodeSecuriteAttestationGen rejected the request (HTTP 400): no error code.',
            $exception->getMessage(),
        );
    }

    public function testAuthenticationFailed(): void
    {
        $exception = UrssafAuthenticationFailed::withStatus(401);

        static::assertSame('URSSAF authentication or authorisation failed (HTTP 401).', $exception->getMessage());
        static::assertSame(401, $exception->httpStatus());
        static::assertSame(401, $exception->getCode());
    }

    public function testUnavailableWithStatus(): void
    {
        $exception = UrssafUnavailable::withStatus(Operation::VerifyCertificates, 429, 30);

        static::assertSame(
            'URSSAF operation verifierListeCodesSecuriteAttestationGen is temporarily unavailable (HTTP 429).',
            $exception->getMessage(),
        );
        static::assertSame(Operation::VerifyCertificates, $exception->operation());
        static::assertSame(429, $exception->httpStatus());
        static::assertSame(429, $exception->getCode());
        static::assertSame(30, $exception->retryAfterSeconds());
        static::assertTrue($exception->isQuotaExceeded());
        static::assertNull($exception->getPrevious());
        static::assertFalse($exception->mayHaveReachedUrssaf());

        $serverError = UrssafUnavailable::withStatus(Operation::VerifyCertificate, 500, null);
        static::assertFalse($serverError->isQuotaExceeded());
        static::assertNull($serverError->retryAfterSeconds());
        static::assertTrue($serverError->mayHaveReachedUrssaf());
        static::assertFalse(UrssafUnavailable::withStatus(Operation::VerifyCertificate, 499, null)->mayHaveReachedUrssaf());
    }

    public function testUnavailableWhileAuthenticating(): void
    {
        $previous = new RuntimeException('Connection refused');

        $exception = UrssafUnavailable::whileAuthenticating(Operation::VerifyCertificate, 503, 12, $previous);

        static::assertSame(
            'URSSAF authentication for operation verifierCodeSecuriteAttestationGen is temporarily unavailable.',
            $exception->getMessage(),
        );
        static::assertSame(Operation::VerifyCertificate, $exception->operation());
        static::assertSame(503, $exception->httpStatus());
        static::assertSame(503, $exception->getCode());
        static::assertSame(12, $exception->retryAfterSeconds());
        static::assertSame($previous, $exception->getPrevious());
        static::assertFalse($exception->mayHaveReachedUrssaf());
        static::assertSame(0, UrssafUnavailable::whileAuthenticating(Operation::VerifyCertificate, null, null, null)->getCode());
    }

    public function testUnavailableDueToTransportError(): void
    {
        $previous = new RuntimeException('Connection refused');

        $exception = UrssafUnavailable::dueToTransportError(Operation::VerifyCertificate, $previous);

        static::assertSame('URSSAF operation verifierCodeSecuriteAttestationGen could not be reached.', $exception->getMessage());
        static::assertSame(Operation::VerifyCertificate, $exception->operation());
        static::assertNull($exception->httpStatus());
        static::assertSame(0, $exception->getCode());
        static::assertNull($exception->retryAfterSeconds());
        static::assertFalse($exception->isQuotaExceeded());
        static::assertSame($previous, $exception->getPrevious());
        static::assertTrue($exception->mayHaveReachedUrssaf());
    }
}
