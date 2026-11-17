<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CommandOptions;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CommandReports;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\VerifyCertificateCommand;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\HttpScript;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

#[CoversClass(VerifyCertificateCommand::class)]
#[CoversClass(CommandOptions::class)]
#[CoversClass(CommandReports::class)]
final class VerifyCertificateCommandFailuresTest extends TestCase
{
    private const COMMAND = 'urssaf:certificate:verify';

    public function testAnUnknownFormatIsRejected(): void
    {
        $harness = Harness::boot();
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999'], '--format' => 'xml']));
        static::assertSame('The --format option must be "table" or "json".', trim($tester->getDisplay()));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
    }

    public function testABlankCodeIsRejected(): void
    {
        $harness = Harness::boot();
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['  ']]));
        static::assertSame('Invalid security_code: the value must not be empty.', trim($tester->getDisplay()));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
    }

    public function testAUrssafFailureFailsTheCommandWithItsMessage(): void
    {
        $harness = Harness::boot();
        $harness->urssaf->respond(HttpScript::ROUTE_VERIFY, 500, Fixtures::load('error_technical'));
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999']]));
        static::assertSame('URSSAF operation verifierCodeSecuriteAttestationGen is temporarily unavailable (HTTP 500).', trim($tester->getDisplay()));
    }

    public function testTheDefinitionOfTheCommand(): void
    {
        $harness = Harness::boot();
        $definition = (new Application($harness->kernel))->find(self::COMMAND)->getDefinition();

        static::assertTrue($definition->getArgument('codes')->isArray());
        static::assertTrue($definition->getArgument('codes')->isRequired());
        static::assertSame('table', $definition->getOption('format')->getDefault());
        static::assertSame('Output format: table or json', $definition->getOption('format')->getDescription());
        static::assertTrue($definition->getOption('tenant')->isValueRequired());
    }
}
