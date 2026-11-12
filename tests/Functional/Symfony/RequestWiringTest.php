<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\Reference;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\ServiceIds;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\App\TestKernel;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\HttpScript;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\CapturingLogger;

final class RequestWiringTest extends TestCase
{
    private const VALID_CODE = '42TROLLQU3DT999';

    public function testTheDomainParameterIsOmittedWhenConfiguredToNull(): void
    {
        $harness = Harness::boot(['domain' => null]);

        $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        static::assertSame('codeSecurite=' . self::VALID_CODE, self::lastRequest($harness)->getUri()->getQuery());
    }

    public function testTheIsuDomainIsSent(): void
    {
        $harness = Harness::boot(['domain' => 'ISU']);

        $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        static::assertSame('codeSecurite=' . self::VALID_CODE . '&domaine=ISU', self::lastRequest($harness)->getUri()->getQuery());
    }

    public function testTheBaseUriOverrideIsUsedForTheTokenAndTheApi(): void
    {
        $harness = Harness::boot(['base_uri' => 'https://proxy.example.test']);

        $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        $requests = $harness->client->getRequests();
        static::assertSame('https://proxy.example.test/api/oauth/v1/token', (string) $requests[0]->getUri());
        static::assertSame('proxy.example.test', $requests[1]->getUri()->getHost());
    }

    public function testTheAccessTokenIsRequestedOnceForSeveralCalls(): void
    {
        $harness = Harness::boot();
        $handler = $harness->get(VerifyCertificateHandler::class);
        $query = new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE));

        $handler($query);
        $handler($query);

        static::assertSame(1, $harness->urssaf->calls(HttpScript::ROUTE_AUTHENTICATION));
        static::assertSame(2, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
    }

    public function testTheSecretOfTheConfiguredCredentialsIsSentToTheTokenEndpoint(): void
    {
        $harness = Harness::boot();

        $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        $body = (string) $harness->client->getRequests()[0]->getBody();
        static::assertStringContainsString('client_id=client-1', $body);
        static::assertStringContainsString('client_secret=' . TestKernel::SECRET, $body);
    }

    public function testTheGatewayLogsOnTheApplicationLogger(): void
    {
        $harness = Harness::boot();

        $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        $logger = $harness->privateService('logger', CapturingLogger::class);
        static::assertNotSame([], $logger->records());
        static::assertStringNotContainsString(TestKernel::SECRET, $logger->dump());
    }

    public function testTheDefaultHttpClientSendsTheRequestsOfTheGateway(): void
    {
        $container = (new TestKernel(['http_client' => null]))->definitions();

        static::assertEquals(
            [new Reference(ServiceIds::PSR18_CLIENT), new Reference(ServiceIds::PSR18_CLIENT), new Reference(ServiceIds::PSR18_CLIENT)],
            $container->getDefinition(ServiceIds::HTTP_TRANSPORT)->getArguments(),
        );
    }

    private static function lastRequest(Harness $harness): RequestInterface
    {
        $requests = $harness->client->getRequests();
        $last = end($requests);
        static::assertInstanceOf(RequestInterface::class, $last);

        return $last;
    }
}
