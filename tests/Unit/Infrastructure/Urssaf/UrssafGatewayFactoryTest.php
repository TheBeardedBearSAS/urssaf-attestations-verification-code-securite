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

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\GatewayOptions;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafGatewayFactory;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UtcSystemClock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\CapturingLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\InMemoryTokenStore;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\SequenceIdGenerator;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\StaticCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\SteppingClock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

#[CoversClass(UrssafGatewayFactory::class)]
#[CoversClass(GatewayOptions::class)]
final class UrssafGatewayFactoryTest extends TestCase
{
    public function testCreatesAWorkingGatewayWithTheDefaults(): void
    {
        $http = new Client();
        $http->addResponse(new Response(200, [], Fixtures::load('token_response')));
        $http->addResponse(new Response(200, [], Fixtures::load('verify_response_valid')));
        $factory = new Psr17Factory();
        $tokens = new InMemoryTokenStore();

        $gateway = UrssafGatewayFactory::create(
            new HttpTransport($http, $factory, $factory),
            new StaticCredentialsProvider(),
            $tokens,
            new ApiConfiguration(Environment::Sandbox, null, null, 60, 'agent'),
        );
        $result = $gateway->verify(Stacks::tenant(), SecurityCode::fromString('42TROLLQU3DT999'));

        static::assertInstanceOf(VerifiedCertificate::class, $result);
        $requests = $http->getRequests();
        static::assertCount(2, $requests);
        static::assertSame('https://api-edi.urssaf.fr/api/oauth/v1/token', (string) $requests[0]->getUri());
        static::assertSame(
            'https://api-edi.urssaf.fr/attn/verification/v1/codes_securite/verifier?codeSecurite=42TROLLQU3DT999',
            (string) $requests[1]->getUri(),
        );
        static::assertSame('Bearer fictitious-access-token-0001', $requests[1]->getHeaderLine('Authorization'));
        static::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $requests[1]->getHeaderLine('x-request-id'),
        );
        static::assertSame('agent', $requests[1]->getHeaderLine('User-Agent'));
        static::assertCount(1, $tokens->keys());
    }

    public function testUsesTheGivenCollaborators(): void
    {
        $http = new Client();
        $http->addResponse(new Response(200, [], Fixtures::load('token_response')));
        $http->addResponse(new Response(200, [], Fixtures::load('verify_response_valid')));
        $factory = new Psr17Factory();
        $logger = new CapturingLogger();

        $gateway = UrssafGatewayFactory::createWith(
            new HttpTransport($http, $factory, $factory),
            new StaticCredentialsProvider(),
            new InMemoryTokenStore(),
            ApiConfiguration::forEnvironment(Environment::Sandbox),
            GatewayOptions::defaults()
                ->withClock(new SteppingClock(250_000))
                ->withLogger($logger)
                ->withIdGenerator(new SequenceIdGenerator()),
        );

        $gateway->verify(Stacks::tenant(), SecurityCode::fromString('42TROLLQU3DT999'));

        $records = $logger->records();
        static::assertSame(['URSSAF token request.', 'URSSAF call.'], array_column($records, 'message'));
        static::assertSame(250, $records[1]['context']['duration_ms'] ?? null);
        static::assertSame('request-id-1', $http->getRequests()[1]->getHeaderLine('x-request-id'));
    }

    public function testTheLoggerAlsoReachesTheDetailsMapper(): void
    {
        $body = '{"verifierCodeSecuriteAttestationGen":[{"codeSecurite":"A","infosSecurisation":'
            . '{"infosSecurisationSpecifiques":[{"infosSecurisationSpecifiquesXYZ":{}}]}}]}';
        $http = new Client();
        $http->addResponse(new Response(200, [], Fixtures::load('token_response')));
        $http->addResponse(new Response(200, [], $body));
        $factory = new Psr17Factory();
        $logger = new CapturingLogger();

        UrssafGatewayFactory::createWith(
            new HttpTransport($http, $factory, $factory),
            new StaticCredentialsProvider(),
            new InMemoryTokenStore(),
            ApiConfiguration::forEnvironment(Environment::Sandbox),
            GatewayOptions::defaults()->withLogger($logger),
        )->verify(Stacks::tenant(), SecurityCode::fromString('A'));

        $warnings = array_values(array_filter($logger->records(), static fn(array $record): bool => $record['level'] === 'warning'));
        static::assertCount(1, $warnings);
        static::assertSame('Unknown URSSAF certificate details key ignored', $warnings[0]['message']);
        static::assertSame(['key' => 'infosSecurisationSpecifiquesXYZ'], $warnings[0]['context']);
    }

    public function testOptionsKeepEveryGivenCollaborator(): void
    {
        $ids = new SequenceIdGenerator();
        $clock = new UtcSystemClock();
        $logger = new NullLogger();

        $defaults = GatewayOptions::defaults();
        $options = $defaults->withIdGenerator($ids)->withClock($clock)->withLogger($logger);

        static::assertNull($defaults->idGenerator());
        static::assertNull($defaults->clock());
        static::assertNull($defaults->logger());
        static::assertSame($ids, $options->idGenerator());
        static::assertSame($clock, $options->clock());
        static::assertSame($logger, $options->logger());
        static::assertSame($ids, $defaults->withClock($clock)->withIdGenerator($ids)->withLogger($logger)->idGenerator());
        static::assertSame($clock, $defaults->withLogger($logger)->withIdGenerator($ids)->withClock($clock)->clock());
        static::assertSame($logger, $defaults->withIdGenerator($ids)->withClock($clock)->withLogger($logger)->logger());
    }
}
