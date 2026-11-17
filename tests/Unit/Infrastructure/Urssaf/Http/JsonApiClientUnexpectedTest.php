<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\UrssafException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\ApiRequest;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\Payload;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\TransportFailure;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

final class JsonApiClientUnexpectedTest extends TestCase
{
    private function call(int $status, string $body): Payload
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond($status, $body);
        $stack->respondToken();
        $stack->respond($status, $body);

        return $stack->jsonClient()->send(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::post(Operation::VerifyCertificates, '/p', '[]'),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function unexpectedStatuses(): iterable
    {
        yield 'multiple choices' => [300];
        yield 'redirect' => [302];
        yield 'not found' => [404];
        yield 'teapot' => [418];
        yield 'bad gateway' => [502];
        yield 'gateway timeout' => [504];
        yield 'informational' => [199];
    }

    #[DataProvider('unexpectedStatuses')]
    public function testAnyOtherStatusIsUnexpected(int $status): void
    {
        try {
            $this->call($status, '{}');
            static::fail('Expected an unexpected-response exception.');
        } catch (UnexpectedUrssafResponse $exception) {
            static::assertContains(UrssafException::class, class_implements($exception));
            static::assertSame($status, $exception->getCode());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unusableSuccessBodies(): iterable
    {
        yield 'empty' => [''];
        yield 'not json' => ['<html>'];
        yield 'scalar' => ['"ok"'];
    }

    #[DataProvider('unusableSuccessBodies')]
    public function testASuccessWithAnUnusableBodyIsUnexpected(string $body): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);

        $this->call(200, $body);
    }

    public function testTransportErrorsAreRetryableAndKeepTheCause(): void
    {
        $stack = Stacks::sandbox();
        $stack->warmUpToken();
        $cause = new TransportFailure('timeout');
        $stack->fail($cause);

        try {
            $stack->jsonClient()->send(
                $stack->credentials->forTenant(Stacks::tenant()),
                ApiRequest::get(Operation::VerifyCertificates, '/p', []),
            );
            static::fail('Expected an unavailable exception.');
        } catch (UrssafUnavailable $exception) {
            static::assertNull($exception->httpStatus());
            static::assertSame($cause, $exception->getPrevious());
            static::assertSame(Operation::VerifyCertificates, $exception->operation());
        }
    }
}
