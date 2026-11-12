<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificatesHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\HttpScript;

/**
 * The handlers of the bundle run end to end against the official fixtures, through the scripted HTTP client.
 */
final class VerificationWiringTest extends TestCase
{
    private const VALID_CODE = '42TROLLQU3DT999';
    private const EXPIRED_CODE = '4W6UKDR41G457CW';

    public function testTheHandlersArePublicAndTheGatewayIsPrivate(): void
    {
        $harness = Harness::boot();

        static::assertTrue($harness->has(VerifyCertificateHandler::class));
        static::assertTrue($harness->has(VerifyCertificatesHandler::class));
        static::assertFalse($harness->has(CertificateVerificationGateway::class));
        static::assertInstanceOf(
            UrssafHttpGateway::class,
            $harness->privateService(CertificateVerificationGateway::class, CertificateVerificationGateway::class),
        );
    }

    public function testOneCertificateIsVerifiedEndToEnd(): void
    {
        $harness = Harness::boot();

        $result = $harness->get(VerifyCertificateHandler::class)(
            new VerifyCertificate(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::VALID_CODE)),
        );

        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertSame(self::VALID_CODE, $result->securityCode()->value());
        static::assertSame('CSG_RSI', $result->natureCode());
        static::assertSame(1, $harness->urssaf->calls(HttpScript::ROUTE_AUTHENTICATION));
        static::assertSame(1, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY_MANY));
        $request = self::lastRequest($harness);
        static::assertSame('https://api-edi.urssaf.fr/attn/verification/v1/codes_securite/verifier', $request->getUri()->withQuery('')->__toString());
        static::assertSame('codeSecurite=' . self::VALID_CODE . '&domaine=URSSAF', $request->getUri()->getQuery());
        static::assertSame('Bearer fictitious-access-token-0001', $request->getHeaderLine('Authorization'));
    }

    public function testSeveralCertificatesAreVerifiedEndToEnd(): void
    {
        $harness = Harness::boot();

        $results = $harness->get(VerifyCertificatesHandler::class)(
            new VerifyCertificates(TenantId::fromString('tenant-1'), SecurityCode::fromString(self::EXPIRED_CODE)),
        );

        static::assertCount(1, $results);
        static::assertInstanceOf(RejectedCode::class, $results[0]);
        static::assertSame('FUNC201', $results[0]->faultCode());
        static::assertSame(1, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY_MANY));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
        static::assertStringContainsString(self::EXPIRED_CODE, (string) self::lastRequest($harness)->getBody());
    }

    private static function lastRequest(Harness $harness): RequestInterface
    {
        $requests = $harness->client->getRequests();
        $last = end($requests);
        static::assertInstanceOf(RequestInterface::class, $last);

        return $last;
    }
}
