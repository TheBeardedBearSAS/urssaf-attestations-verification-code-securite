<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Contract;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafRequestRejected;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\FaultCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;

/**
 * Live checks against the URSSAF sandbox. Opt-in, never part of coverage or mutation gates.
 *
 * Optional environment: URSSAF_SANDBOX_VALID_SECURITY_CODE (a code known to be valid; the tests needing it are
 * skipped without it), URSSAF_SANDBOX_UNKNOWN_SECURITY_CODE (default "xxx").
 */
#[Group('contract')]
final class SandboxContractTest extends TestCase
{
    private SandboxGateway $sandbox;

    protected function setUp(): void
    {
        $sandbox = SandboxGateway::fromEnvironment();
        if (!$sandbox instanceof SandboxGateway) {
            static::markTestSkipped(
                'URSSAF sandbox contract tests are opt-in: set URSSAF_SANDBOX_CLIENT_ID and URSSAF_SANDBOX_CLIENT_SECRET (see `make test-contract`).',
            );
        }

        $this->sandbox = $sandbox;
    }

    public function testUnknownCodeIsRejectedAsUnknownWithTheDomainParameter(): void
    {
        $result = $this->sandbox->withDomain->verify($this->sandbox->tenantId, SandboxGateway::unknownSecurityCode());

        static::assertInstanceOf(RejectedCode::class, $result);
        static::assertSame(FaultCode::Unknown, $result->fault());
    }

    /**
     * Settles api-reference section 5 ("domaine"): the PDF documents the parameter as mandatory, the Swagger omits
     * it. A FUNC202 rejection (not a request error) without it proves the parameter is optional; that it is ignored
     * is proven by the valid-code pair (testVerifiesAValidCode and testVerifiesAValidCodeWithoutTheDomainParameter).
     */
    public function testUnknownCodeIsRejectedAsUnknownWithoutTheDomainParameter(): void
    {
        $result = $this->verifyWithoutDomain(SandboxGateway::unknownSecurityCode());

        static::assertInstanceOf(RejectedCode::class, $result);
        static::assertSame(FaultCode::Unknown, $result->fault());
    }

    /**
     * Second half of the "domaine" check: a valid code must give the same certificate with and without the parameter.
     */
    public function testVerifiesAValidCodeWithoutTheDomainParameter(): void
    {
        $valid = $this->validCode();

        $with = $this->sandbox->withDomain->verify($this->sandbox->tenantId, $valid);
        $without = $this->verifyWithoutDomain($valid);

        static::assertInstanceOf(VerifiedCertificate::class, $with);
        static::assertInstanceOf(VerifiedCertificate::class, $without);
        static::assertTrue($with->securityCode()->equals($without->securityCode()));
        static::assertSame($with->natureCode(), $without->natureCode());
    }

    public function testVerifiesSeveralUnknownCodesInOrder(): void
    {
        $first = SandboxGateway::unknownSecurityCode();
        $second = SecurityCode::fromString($first->value() . 'y');

        $results = $this->sandbox->withDomain->verifyMany($this->sandbox->tenantId, $first, $second);

        static::assertCount(2, $results);
        static::assertInstanceOf(RejectedCode::class, $results[0]);
        static::assertInstanceOf(RejectedCode::class, $results[1]);
        static::assertSame(FaultCode::Unknown, $results[0]->fault());
        static::assertSame(FaultCode::Unknown, $results[1]->fault());
        static::assertTrue($first->equals($results[0]->securityCode()));
        static::assertTrue($second->equals($results[1]->securityCode()));
    }

    public function testVerifiesAValidCode(): void
    {
        $valid = $this->validCode();

        $result = $this->sandbox->withDomain->verify($this->sandbox->tenantId, $valid);

        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertNotNull($result->nature());
        static::assertTrue($valid->equals($result->securityCode()));
    }

    public function testVerifiesAValidAndAnUnknownCodeTogether(): void
    {
        $valid = $this->validCode();
        $unknown = SandboxGateway::unknownSecurityCode();

        $results = $this->sandbox->withDomain->verifyMany($this->sandbox->tenantId, $valid, $unknown);

        static::assertCount(2, $results);
        static::assertInstanceOf(VerifiedCertificate::class, $results[0]);
        static::assertInstanceOf(RejectedCode::class, $results[1]);
        static::assertSame(FaultCode::Unknown, $results[1]->fault());
    }

    private function verifyWithoutDomain(SecurityCode $code): VerificationResult
    {
        try {
            return $this->sandbox->withoutDomain->verify($this->sandbox->tenantId, $code);
        } catch (UrssafRequestRejected $rejected) {
            static::fail(\sprintf(
                'The sandbox requires the "domaine" parameter (api-reference section 5 confirmed: keep the URSSAF default). HTTP %d, error codes: %s.',
                $rejected->httpStatus(),
                implode(', ', $rejected->errors()->codes()),
            ));
        }
    }

    private function validCode(): SecurityCode
    {
        $valid = SandboxGateway::validSecurityCode();
        if (!$valid instanceof SecurityCode) {
            static::markTestSkipped('Set URSSAF_SANDBOX_VALID_SECURITY_CODE (a security code known to be valid in the sandbox) to run this test.');
        }

        return $valid;
    }
}
