<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafRequestRejected;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\TransportFailure;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Gateways;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

#[CoversClass(UrssafHttpGateway::class)]
final class UrssafHttpGatewayErrorsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function badRequests(): iterable
    {
        yield 'service failure' => ['error_func013', 'FUNC013'];
        yield 'empty list' => ['error_func015', 'FUNC015'];
        yield 'too many codes' => ['error_too_many_codes', 'FUNC013'];
    }

    #[DataProvider('badRequests')]
    public function testAnHttp400OnTheGetOperationIsARejectionWithItsCodes(string $fixture, string $code): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(400, Fixtures::load($fixture));

        try {
            Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('A'));
            static::fail('Expected a rejection.');
        } catch (UrssafRequestRejected $exception) {
            static::assertSame(400, $exception->httpStatus());
            static::assertSame(Operation::VerifyCertificate, $exception->operation());
            static::assertSame([$code], $exception->errors()->codes());
        }
    }

    #[DataProvider('badRequests')]
    public function testAnHttp400OnThePostOperationIsARejectionWithItsCodes(string $fixture, string $code): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(400, Fixtures::load($fixture));

        try {
            Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes(['A']));
            static::fail('Expected a rejection.');
        } catch (UrssafRequestRejected $exception) {
            static::assertSame(Operation::VerifyCertificates, $exception->operation());
            static::assertSame([$code], $exception->errors()->codes());
        }
    }

    public function testAnHttp500TechnicalErrorIsUnavailable(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(500, Fixtures::load('error_technical'));

        try {
            Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('A'));
            static::fail('Expected an unavailability.');
        } catch (UrssafUnavailable $exception) {
            static::assertSame(500, $exception->httpStatus());
            static::assertNull($exception->retryAfterSeconds());
            static::assertTrue($exception->mayHaveReachedUrssaf());
            static::assertFalse($exception->isQuotaExceeded());
        }
    }

    public function testAnHttp429CarriesTheRetryAfterDelay(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(429, '', ['Retry-After' => '30']);

        try {
            Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes(['A']));
            static::fail('Expected an unavailability.');
        } catch (UrssafUnavailable $exception) {
            static::assertSame(429, $exception->httpStatus());
            static::assertSame(30, $exception->retryAfterSeconds());
            static::assertTrue($exception->isQuotaExceeded());
            static::assertFalse($exception->mayHaveReachedUrssaf());
            static::assertSame(Operation::VerifyCertificates, $exception->operation());
        }
    }

    public function testATransportErrorIsUnavailable(): void
    {
        $stack = Stacks::sandbox();
        $stack->warmUpToken();
        $stack->fail(new TransportFailure('boom'));

        $this->expectException(UrssafUnavailable::class);
        $this->expectExceptionMessage('URSSAF operation verifierCodeSecuriteAttestationGen could not be reached.');

        Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('A'));
    }
}
