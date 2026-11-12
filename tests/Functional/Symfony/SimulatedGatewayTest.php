<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificatesHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\HttpScript;

/**
 * "gateway.simulated: true": the handlers run against the in-memory URSSAF API, as in the tests of a host application.
 */
final class SimulatedGatewayTest extends TestCase
{
    public function testTheRealGatewayIsUsedByDefault(): void
    {
        $harness = Harness::boot();

        static::assertInstanceOf(UrssafHttpGateway::class, $harness->privateService(CertificateVerificationGateway::class, CertificateVerificationGateway::class));
        static::assertFalse($harness->has(SimulatedCertificateVerificationGateway::class));
    }

    public function testTheHandlersAnswerFromTheSimulatorWithoutNetwork(): void
    {
        $harness = Harness::boot(['gateway' => ['simulated' => true]]);
        $simulator = $harness->get(SimulatedCertificateVerificationGateway::class);
        static::assertSame($simulator, $harness->privateService(CertificateVerificationGateway::class, CertificateVerificationGateway::class));
        $tenant = TenantId::fromString('tenant-1');

        $one = $harness->get(VerifyCertificateHandler::class)(new VerifyCertificate($tenant, SecurityCode::fromString('UNKNOWN1')));
        $many = $harness->get(VerifyCertificatesHandler::class)(new VerifyCertificates($tenant, SecurityCode::fromString('UNKNOWN2')));

        static::assertInstanceOf(RejectedCode::class, $one);
        static::assertSame('FUNC202', $one->faultCode());
        static::assertCount(1, $many);
        static::assertSame(
            [Operation::VerifyCertificate, Operation::VerifyCertificates],
            array_map(static fn(array $call): Operation => $call['operation'], $simulator->calls()),
        );
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_AUTHENTICATION));
        static::assertSame([], $harness->client->getRequests());
    }
}
