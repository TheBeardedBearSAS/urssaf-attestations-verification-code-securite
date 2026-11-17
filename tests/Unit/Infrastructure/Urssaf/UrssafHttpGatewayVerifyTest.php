<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\VerificationDomain;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Gateways;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

#[CoversClass(UrssafHttpGateway::class)]
final class UrssafHttpGatewayVerifyTest extends TestCase
{
    private const URL = 'https://api-edi.urssaf.fr/attn/verification/v1/codes_securite/verifier';

    public function testAValidCertificateIsMapped(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, Fixtures::load('verify_response_valid'));

        $result = Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('42TROLLQU3DT999'));

        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertTrue($result->isValid());
        static::assertSame('42TROLLQU3DT999', $result->securityCode()->value());
        static::assertSame('CSG_RSI', $result->natureCode());
        static::assertSame('Cooper Sheldon', $result->denomination());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejections(): iterable
    {
        yield 'expired' => ['verify_response_expired', 'FUNC201'];
        yield 'unknown' => ['verify_response_unknown', 'FUNC202'];
        yield 'missing data' => ['verify_response_missing_data', 'FUNC203'];
    }

    #[DataProvider('rejections')]
    public function testAFunctionalFaultIsMappedToARejectedCode(string $fixture, string $faultCode): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, Fixtures::load($fixture));

        $result = Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('4W6UKDR41G457CW'));

        static::assertInstanceOf(RejectedCode::class, $result);
        static::assertFalse($result->isValid());
        static::assertSame($faultCode, $result->faultCode());
    }

    public function testTheRequestCarriesTheCodeAndTheDefaultDomain(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, Fixtures::load('verify_response_expired'));

        Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString(' 4W6UKDR41G457CW '));

        $request = $stack->lastRequest();
        static::assertSame('GET', $request->getMethod());
        static::assertSame(self::URL . '?codeSecurite=4W6UKDR41G457CW&domaine=URSSAF', (string) $request->getUri());
        static::assertSame('', (string) $request->getBody());
        static::assertCount(2, $stack->requests());
    }

    public function testTheConfiguredDomainIsSent(): void
    {
        $stack = Stacks::configured(new ApiConfiguration(Environment::Sandbox, null, VerificationDomain::Isu));
        $stack->respondToken();
        $stack->respond(200, Fixtures::load('verify_response_expired'));

        Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('4W6UKDR41G457CW'));

        static::assertSame(self::URL . '?codeSecurite=4W6UKDR41G457CW&domaine=ISU', (string) $stack->lastRequest()->getUri());
    }

    public function testTheDomainIsOmittedWhenNull(): void
    {
        $stack = Stacks::configured(new ApiConfiguration(Environment::Sandbox, null, null));
        $stack->respondToken();
        $stack->respond(200, Fixtures::load('verify_response_expired'));

        Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('4W6UKDR41G457CW'));

        static::assertSame(self::URL . '?codeSecurite=4W6UKDR41G457CW', (string) $stack->lastRequest()->getUri());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function wrongCounts(): iterable
    {
        yield 'none' => ['{"verifierCodeSecuriteAttestationGen":[]}', 'Unexpected URSSAF response: expected exactly one result, got 0.'];
        yield 'two' => [
            '{"verifierCodeSecuriteAttestationGen":[{"codeSecurite":"A","functionalFaultCodeSecurite":{"faultCode":"FUNC201"}},'
            . '{"codeSecurite":"B","functionalFaultCodeSecurite":{"faultCode":"FUNC201"}}]}',
            'Unexpected URSSAF response: expected exactly one result, got 2.',
        ];
    }

    #[DataProvider('wrongCounts')]
    public function testAResponseWithoutExactlyOneResultIsUnexpected(string $body, string $message): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, $body);

        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage($message);

        Gateways::on($stack)->verify(Stacks::tenant(), SecurityCode::fromString('A'));
    }
}
