<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Fake;

use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

#[CoversNothing]
final class FakeCertificateVerificationGatewayTest extends TestCase
{
    public function testRejectsUnscriptedCalls(): void
    {
        $gateway = new FakeCertificateVerificationGateway();
        $tenant = TenantId::fromString('tenant-1');
        $code = SecurityCode::fromString('A');

        try {
            $gateway->verify($tenant, $code);
            static::fail('Expected exception');
        } catch (LogicException $exception) {
            static::assertSame('Unexpected verify() call.', $exception->getMessage());
        }

        try {
            $gateway->verifyMany($tenant, $code);
            static::fail('Expected exception');
        } catch (LogicException $exception) {
            static::assertSame('Unexpected verifyMany() call.', $exception->getMessage());
        }
    }
}
