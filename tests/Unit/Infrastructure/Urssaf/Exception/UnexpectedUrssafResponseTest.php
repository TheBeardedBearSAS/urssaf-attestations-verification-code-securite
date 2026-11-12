<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Exception;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\RetryableException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\UrssafException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;

#[CoversClass(UnexpectedUrssafResponse::class)]
final class UnexpectedUrssafResponseTest extends TestCase
{
    public function testInvalidPayloadIsAnUrssafExceptionAndNotRetryable(): void
    {
        $exception = UnexpectedUrssafResponse::invalidPayload('field "x" is missing');

        static::assertContains(UrssafException::class, class_implements($exception));
        static::assertNotContains(RetryableException::class, class_implements($exception));
        static::assertSame('Unexpected URSSAF response: field "x" is missing.', $exception->getMessage());
        static::assertNull($exception->getPrevious());
        static::assertSame(0, $exception->getCode());
    }

    public function testCausedByKeepsThePreviousException(): void
    {
        $previous = new LogicException('boom');
        $exception = UnexpectedUrssafResponse::causedBy('bad value', $previous);

        static::assertSame($previous, $exception->getPrevious());
        static::assertSame('Unexpected URSSAF response: bad value.', $exception->getMessage());
        static::assertSame(0, $exception->getCode());
    }

    public function testUnexpectedStatusNamesTheOperationAndTheStatus(): void
    {
        $exception = UnexpectedUrssafResponse::unexpectedStatus(Operation::VerifyCertificates, 418);

        static::assertSame(
            'Unexpected URSSAF response for operation verifierListeCodesSecuriteAttestationGen: HTTP 418.',
            $exception->getMessage(),
        );
        static::assertSame(418, $exception->getCode());
    }
}
