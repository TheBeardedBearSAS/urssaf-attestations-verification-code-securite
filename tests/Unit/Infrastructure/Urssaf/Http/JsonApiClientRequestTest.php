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

use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\ApiRequest;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

final class JsonApiClientRequestTest extends TestCase
{
    public function testItSendsAnAuthenticatedJsonGetRequest(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, '{"ok":"yes"}');

        $payload = $stack->jsonClient()->send(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::get(Operation::VerifyCertificate, '/codes_securite/verifier', ['codeSecurite' => 'abc']),
        );

        static::assertSame('yes', $payload->string('ok'));
        $request = $stack->lastRequest();
        static::assertSame('GET', $request->getMethod());
        static::assertSame(
            'https://api-edi.urssaf.fr/attn/verification/v1/codes_securite/verifier?codeSecurite=abc',
            (string) $request->getUri(),
        );
        static::assertSame('Bearer fictitious-access-token-0001', $request->getHeaderLine('Authorization'));
        static::assertSame('application/json', $request->getHeaderLine('Accept'));
        static::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        static::assertSame('request-id-1', $request->getHeaderLine('x-request-id'));
        static::assertSame(ApiConfiguration::DEFAULT_USER_AGENT, $request->getHeaderLine('User-Agent'));
        static::assertFalse($request->hasHeader('recouv.application.id'));
        static::assertSame('', (string) $request->getBody());
    }

    public function testItSendsThePostBodyVerbatimWithoutAQueryString(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, '[]');

        $stack->jsonClient()->send(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::post(Operation::VerifyCertificates, '/codes_securite/verifier', '{"codesSecurite":["ABC"]}'),
        );

        $request = $stack->lastRequest();
        static::assertSame('POST', $request->getMethod());
        static::assertSame('https://api-edi.urssaf.fr/attn/verification/v1/codes_securite/verifier', (string) $request->getUri());
        static::assertSame('{"codesSecurite":["ABC"]}', (string) $request->getBody());
    }

    public function testItEncodesQueryValuesAccordingToRfc3986(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, '{}');

        $stack->jsonClient()->send(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::get(Operation::VerifyCertificate, '/p', ['codeSecurite' => 'a b&c', 'x' => 'y']),
        );

        static::assertSame('codeSecurite=a%20b%26c&x=y', $stack->lastRequest()->getUri()->getQuery());
    }

    public function testItUsesTheProductionHostWhenConfigured(): void
    {
        $stack = Stacks::configured(ApiConfiguration::forEnvironment(Environment::Production));
        $stack->respondToken();
        $stack->respond(200, '{}');

        $stack->jsonClient()->send(
            $stack->credentials->forTenant(Stacks::tenant()),
            ApiRequest::get(Operation::VerifyCertificate, '/p', []),
        );

        static::assertSame('https://api.urssaf.fr/attn/verification/v1/p', (string) $stack->lastRequest()->getUri());
    }
}
