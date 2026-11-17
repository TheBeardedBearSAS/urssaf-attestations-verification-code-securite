<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Testing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\IssuingBody;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;

#[CoversClass(SimulatedCertificateVerificationGateway::class)]
final class SimulatedCertificateVerificationGatewayVerifyManyTest extends TestCase
{
    public function testVerifyManyAnswersInOrderOneResultPerCode(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $certificate = VerifiedCertificate::of(SecurityCode::fromString('B'), 'hash', 'AVG_UR', 'ACME', IssuingBody::of('837', 'Urssaf AUVERGNE'), null, []);
        $gateway->certify($certificate);
        $gateway->reject(SecurityCode::fromString('A'), 'FUNC201', 'Attestation perimée depuis le 01/03/2019');

        $results = $gateway->verifyMany(
            TenantId::fromString('t1'),
            SecurityCode::fromString('B'),
            SecurityCode::fromString('A'),
            SecurityCode::fromString('Z'),
            SecurityCode::fromString('B'),
        );

        static::assertCount(4, $results);
        static::assertSame($certificate, $results[0]);
        static::assertInstanceOf(RejectedCode::class, $results[1]);
        static::assertSame('FUNC201', $results[1]->faultCode());
        static::assertInstanceOf(RejectedCode::class, $results[2]);
        static::assertSame('FUNC202', $results[2]->faultCode());
        static::assertSame($certificate, $results[3]);
    }

    public function testVerifyManyAcceptsTheBoundaries(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $tenant = TenantId::fromString('t1');

        static::assertCount(1, $gateway->verifyMany($tenant, SecurityCode::fromString('C0')));

        $codes = [];
        for ($i = 0; $i < CertificateVerificationGateway::MAX_CODES_PER_CALL; ++$i) {
            $codes[] = SecurityCode::fromString('C' . $i);
        }
        static::assertCount(CertificateVerificationGateway::MAX_CODES_PER_CALL, $gateway->verifyMany($tenant, ...$codes));
    }

    public function testVerifyManyRefusesNoCode(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();

        try {
            $gateway->verifyMany(TenantId::fromString('t1'));
            static::fail('Expected an InvariantViolation.');
        } catch (InvariantViolation $e) {
            static::assertSame('security_codes.count_out_of_range', $e->violationCode());
            static::assertSame('The number of security codes must be between 1 and 100.', $e->getMessage());
            static::assertSame(['count' => 0, 'min' => 1, 'max' => 100], $e->params());
        }
        static::assertSame([], $gateway->calls());
    }

    public function testVerifyManyRefusesTooManyCodes(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $codes = [];
        for ($i = 0; $i <= CertificateVerificationGateway::MAX_CODES_PER_CALL; ++$i) {
            $codes[] = SecurityCode::fromString('C' . $i);
        }

        try {
            $gateway->verifyMany(TenantId::fromString('t1'), ...$codes);
            static::fail('Expected an InvariantViolation.');
        } catch (InvariantViolation $e) {
            static::assertSame('security_codes.count_out_of_range', $e->violationCode());
            static::assertSame('The number of security codes must be between 1 and 100.', $e->getMessage());
            static::assertSame(['count' => 101, 'min' => 1, 'max' => 100], $e->params());
        }
        static::assertSame([], $gateway->calls());
    }

    public function testVerifyManyReturnsAndRecordsLists(): void
    {
        $gateway = new SimulatedCertificateVerificationGateway();
        $tenant = TenantId::fromString('t1');
        $a = SecurityCode::fromString('A');
        $b = SecurityCode::fromString('B');

        $results = $gateway->verifyMany($tenant, ...['first' => $a, 'second' => $b]);

        static::assertSame([0, 1], array_keys($results));
        static::assertSame([['operation' => Operation::VerifyCertificates, 'tenantId' => $tenant, 'securityCodes' => [$a, $b]]], $gateway->calls());
    }
}
