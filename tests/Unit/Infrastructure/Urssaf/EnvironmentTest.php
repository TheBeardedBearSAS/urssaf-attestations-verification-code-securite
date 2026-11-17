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

use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;

final class EnvironmentTest extends TestCase
{
    public function testSandboxTargetsTheEditorPlatform(): void
    {
        static::assertSame('https://api-edi.urssaf.fr', Environment::Sandbox->baseUri());
    }

    public function testProductionTargetsThePublicPlatform(): void
    {
        static::assertSame('https://api.urssaf.fr', Environment::Production->baseUri());
    }
}
