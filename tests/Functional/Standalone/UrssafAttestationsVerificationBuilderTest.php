<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Standalone;

use DateTimeImmutable;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\IssuingBody;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Clock\FrozenClock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerification;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerificationBuilder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\CapturingLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\InMemoryTokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

#[CoversClass(UrssafAttestationsVerificationBuilder::class)]
#[CoversClass(UrssafAttestationsVerification::class)]
final class UrssafAttestationsVerificationBuilderTest extends TestCase
{
    private const VALID_CODE = '42TROLLQU3DT999';
    private const EXPIRED_CODE = '4W6UKDR41G457CW';

    public function testOneCertificateIsVerifiedEndToEndOverHttp(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $package = self::builder($http)->build();

        $result = $package->verifyCertificate()(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertSame(self::VALID_CODE, $result->securityCode()->value());
        static::assertSame('CSG_RSI', $result->natureCode());
        $requests = $http->getRequests();
        static::assertCount(2, $requests);
        static::assertSame('POST', $requests[0]->getMethod());
        static::assertSame('https://api-edi.urssaf.fr/api/oauth/v1/token', (string) $requests[0]->getUri());
        static::assertSame('GET', $requests[1]->getMethod());
        static::assertSame('/attn/verification/v1/codes_securite/verifier', $requests[1]->getUri()->getPath());
        static::assertStringContainsString('codeSecurite=' . self::VALID_CODE, $requests[1]->getUri()->getQuery());
        static::assertSame('Bearer fictitious-access-token-0001', $requests[1]->getHeaderLine('Authorization'));
    }

    public function testSeveralCertificatesAreVerifiedEndToEndOverHttp(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_expired')));
        $package = self::builder($http)->build();

        $results = $package->verifyCertificates()(
            new VerifyCertificates(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::EXPIRED_CODE)),
        );

        static::assertCount(1, $results);
        static::assertInstanceOf(RejectedCode::class, $results[0]);
        static::assertSame('FUNC201', $results[0]->faultCode());
        $requests = $http->getRequests();
        static::assertCount(2, $requests);
        static::assertSame('POST', $requests[1]->getMethod());
        static::assertSame('/attn/verification/v1/codes_securite/verifier', $requests[1]->getUri()->getPath());
        static::assertStringContainsString(self::EXPIRED_CODE, (string) $requests[1]->getBody());
    }

    public function testEveryWithMethodReturnsANewBuilderAndLeavesTheOriginalUntouched(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $base = self::builder($http);
        $simulator = new SimulatedCertificateVerificationGateway();
        $derived = [
            $base->withTokenStore(new InMemoryTokenStore()),
            $base->withLogger(new CapturingLogger()),
            $base->withClock(new FrozenClock(new DateTimeImmutable('2030-01-01T00:00:00Z'))),
            $base->withGateway($simulator),
        ];

        foreach ($derived as $builder) {
            static::assertNotSame($base, $builder);
        }
        static::assertSame($simulator, $derived[3]->build()->gateway());
        // The original builder still builds the HTTP gateway.
        static::assertNotSame($simulator, $base->build()->gateway());
        $result = $base->build()->verifyCertificate()(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );
        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertCount(2, $http->getRequests());
    }

    public function testTheSimulatedGatewayReplacesTheHttpGatewayWithoutAnyHttpCall(): void
    {
        $http = new Client();
        $simulator = new SimulatedCertificateVerificationGateway();
        $certificate = VerifiedCertificate::of(
            SecurityCode::fromString(self::VALID_CODE),
            'hash',
            'CSG_RSI',
            'ACME',
            IssuingBody::of('837', 'Urssaf AUVERGNE'),
            null,
            [],
        );
        $simulator->certify($certificate);
        $package = self::builder($http)->withGateway($simulator)->build();
        $tenant = TenantId::fromString('tenant-1');

        $one = $package->verifyCertificate()(new VerifyCertificate($tenant, SecurityCode::fromString(self::VALID_CODE)));
        $many = $package->verifyCertificates()(new VerifyCertificates($tenant, SecurityCode::fromString('OTHER')));

        static::assertSame($certificate, $one);
        static::assertCount(1, $many);
        static::assertInstanceOf(RejectedCode::class, $many[0]);
        static::assertSame('FUNC202', $many[0]->faultCode());
        static::assertSame([], $http->getRequests());
        $operations = array_map(static fn(array $call): Operation => $call['operation'], $simulator->calls());
        static::assertSame([Operation::VerifyCertificate, Operation::VerifyCertificates], $operations);
    }

    public function testTheGivenTokenStoreIsUsedAndTheTokenIsReusedAcrossCalls(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $store = new InMemoryTokenStore();
        $package = self::builder($http)->withTokenStore($store)->build();
        $query = new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE));

        $package->verifyCertificate()($query);
        $package->verifyCertificate()($query);

        static::assertCount(1, $store->keys());
        $token = $store->get($store->keys()[0]);
        static::assertNotNull($token);
        static::assertSame('fictitious-access-token-0001', $token->value());
        // One token call and two API calls.
        $paths = array_map(static fn(RequestInterface $request): string => $request->getUri()->getPath(), $http->getRequests());
        static::assertSame(
            [
                '/api/oauth/v1/token',
                '/attn/verification/v1/codes_securite/verifier',
                '/attn/verification/v1/codes_securite/verifier',
            ],
            $paths,
        );
    }

    public function testTheDefaultTokenStoreCachesTheTokenBetweenCallsOfOnePackage(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $package = self::builder($http)->build();
        $query = new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE));

        $package->verifyCertificate()($query);
        $package->verifyCertificate()($query);

        static::assertCount(3, $http->getRequests());
    }

    public function testTheGivenClockDatesTheCachedToken(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $store = new InMemoryTokenStore();
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01T00:00:00Z'));
        $package = self::builder($http)->withTokenStore($store)->withClock($clock)->build();

        $package->verifyCertificate()(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        $token = $store->get($store->keys()[0]);
        static::assertNotNull($token);
        static::assertSame('2030-01-01T01:00:00+00:00', $token->expiresAt()->format('c'));
    }

    public function testTheGivenLoggerReceivesTheHttpLogs(): void
    {
        $http = new Client();
        $http->addResponse(self::json(Fixtures::load('token_response')));
        $http->addResponse(self::json(Fixtures::load('verify_response_valid')));
        $logger = new CapturingLogger();
        $package = self::builder($http)->withLogger($logger)->build();

        $package->verifyCertificate()(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        $messages = array_map(static fn(array $record): string => $record['message'], $logger->records());
        static::assertSame(['URSSAF token request.', 'URSSAF call.'], $messages);
    }

    private static function builder(Client $http): UrssafAttestationsVerificationBuilder
    {
        $factory = new Psr17Factory();

        return UrssafAttestationsVerificationBuilder::create(
            new HttpTransport($http, $factory, $factory),
            new InMemoryCredentialsProvider(['tenant-1' => Credentials::of('fictitious-client-id', 'fictitious-client-secret-value')]),
            ApiConfiguration::forEnvironment(Environment::Sandbox),
        );
    }

    private static function json(string $body): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], $body);
    }
}
