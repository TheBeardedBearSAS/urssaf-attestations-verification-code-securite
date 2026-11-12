<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafAuthenticationFailed;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafRequestRejected;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\ApiRequest;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\Payload;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

final class JsonApiClientStatusTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    private function call(int $status, string $body, array $headers = []): Payload
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond($status, $body, $headers);
        // Only used when the status is 401: the single replay (new token, then the same status again).
        $stack->respondToken();
        $stack->respond($status, $body, $headers);

        return $stack->jsonClient()->send(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::post(Operation::VerifyCertificates, '/p', '[]'),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function successStatuses(): iterable
    {
        yield '200' => [200];
        yield '201' => [201];
        yield '299' => [299];
    }

    #[DataProvider('successStatuses')]
    public function testTwoHundredStatusesReturnTheDecodedBody(int $status): void
    {
        static::assertSame('v', $this->call($status, '{"k":"v"}')->string('k'));
    }

    public function testA400IsARejectionCarryingTheParsedErrors(): void
    {
        try {
            $this->call(400, Fixtures::load('error_func015'));
            static::fail('Expected a rejection.');
        } catch (UrssafRequestRejected $exception) {
            static::assertSame(400, $exception->httpStatus());
            static::assertSame(Operation::VerifyCertificates, $exception->operation());
            static::assertSame(['FUNC015'], $exception->errors()->codes());
        }
    }

    public function testExchangeAlsoReturnsTheRequestId(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(401, '');
        $stack->respondToken();
        $stack->respond(200, '{"k":"v"}');

        $response = $stack->jsonClient()->exchange(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::post(Operation::VerifyCertificates, '/p', '[]'),
        );

        static::assertSame('v', $response->payload()->string('k'));
        static::assertSame('request-id-2', $response->requestId());
    }

    public function testA400WithAnUnreadableBodyIsStillARejection(): void
    {
        try {
            $this->call(400, '<html>Bad Request</html>');
            static::fail('Expected a rejection.');
        } catch (UrssafRequestRejected $exception) {
            static::assertTrue($exception->errors()->isEmpty());
        }
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function authenticationStatuses(): iterable
    {
        yield 'unauthorized' => [401];
        yield 'forbidden' => [403];
        yield 'security gateway' => [512];
    }

    #[DataProvider('authenticationStatuses')]
    public function testSecurityStatusesFailAuthentication(int $status): void
    {
        try {
            $this->call($status, '');
            static::fail('Expected an authentication failure.');
        } catch (UrssafAuthenticationFailed $exception) {
            static::assertSame($status, $exception->httpStatus());
        }
    }

    /**
     * @return iterable<string, array{int, array<string, string>, int|null}>
     */
    public static function retryableStatuses(): iterable
    {
        yield 'quota with seconds' => [429, ['Retry-After' => '12'], 12];
        yield 'quota without header' => [429, [], null];
        yield 'quota with http date' => [429, ['Retry-After' => 'Wed, 21 Oct 2026 07:28:00 GMT'], null];
        yield 'technical error' => [500, [], null];
        yield 'unavailable with seconds' => [503, ['Retry-After' => '3'], 3];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('retryableStatuses')]
    public function testQuotaAndServerErrorsAreRetryable(int $status, array $headers, ?int $retryAfter): void
    {
        try {
            $this->call($status, Fixtures::load('error_technical'), $headers);
            static::fail('Expected an unavailable exception.');
        } catch (UrssafUnavailable $exception) {
            static::assertSame($status, $exception->httpStatus());
            static::assertSame($retryAfter, $exception->retryAfterSeconds());
            static::assertSame($status === 429, $exception->isQuotaExceeded());
            static::assertSame(Operation::VerifyCertificates, $exception->operation());
            static::assertSame($status !== 429, $exception->mayHaveReachedUrssaf());
        }
    }
}
