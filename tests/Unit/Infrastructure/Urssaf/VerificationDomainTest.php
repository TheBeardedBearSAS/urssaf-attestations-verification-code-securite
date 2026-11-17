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
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\VerificationDomain;

#[CoversClass(VerificationDomain::class)]
final class VerificationDomainTest extends TestCase
{
    public function testItExposesTheWireValues(): void
    {
        static::assertSame('URSSAF', VerificationDomain::Urssaf->value);
        static::assertSame('ISU', VerificationDomain::Isu->value);
    }

}
