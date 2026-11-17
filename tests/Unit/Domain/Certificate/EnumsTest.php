<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Certificate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\CertificateNature;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\FaultCode;

#[CoversClass(CertificateNature::class)]
#[CoversClass(FaultCode::class)]
final class EnumsTest extends TestCase
{
    public function testNatureWireValues(): void
    {
        static::assertSame(
            [
                'AVG_UR', 'AVG_RSI', 'CSG_UR', 'CSG_RSI', 'CFP_UR', 'CFP_RSI', 'EPM_UR', 'FRS_UR',
                'CAA_UR', 'CAA_RSI', 'CAP_UR', 'CAP_RSI', 'ASAA_UR',
            ],
            array_map(static fn(CertificateNature $nature): string => $nature->value, CertificateNature::cases()),
        );
        static::assertSame(CertificateNature::ArtistAuthor, CertificateNature::tryFrom('ASAA_UR'));
        static::assertNull(CertificateNature::tryFrom('XXX'));
    }

    public function testFaultWireValues(): void
    {
        static::assertSame('FUNC201', FaultCode::Expired->value);
        static::assertSame('FUNC202', FaultCode::Unknown->value);
        static::assertSame('FUNC203', FaultCode::MissingSecurityData->value);
        static::assertCount(3, FaultCode::cases());
        static::assertNull(FaultCode::tryFrom('FUNC999'));
    }
}
