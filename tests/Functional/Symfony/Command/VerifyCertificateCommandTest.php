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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CommandOptions;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CommandReports;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\VerifyCertificateCommand;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\HttpScript;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\ManyResponses;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Output;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

#[CoversClass(VerifyCertificateCommand::class)]
#[CoversClass(CommandOptions::class)]
#[CoversClass(CommandReports::class)]
final class VerifyCertificateCommandTest extends TestCase
{
    private const COMMAND = 'urssaf:certificate:verify';
    private const HEADER = ['Code', 'Result', 'Nature', 'Issuing body', 'Issued on', 'Fault'];
    private const EXPIRED_FAULT = 'FUNC201 - Attestation perimée depuis le 01/03/2019';

    public function testAValidCodeIsPrintedWithoutPersonalData(): void
    {
        $harness = Harness::boot();
        $tester = $harness->command(self::COMMAND);

        static::assertSame(0, $tester->execute(['codes' => ['42TROLLQU3DT999']]));
        static::assertSame(
            [self::HEADER, ['42TROLLQU3DT999', 'valid', 'CSG_RSI', 'Urssaf AUVERGNE', '2020-10-02', '']],
            Output::rows($tester->getDisplay()),
        );
        static::assertStringNotContainsString('Cooper', $tester->getDisplay());
        static::assertStringNotContainsString('Sheldon', $tester->getDisplay());
        static::assertStringNotContainsString('837000000022444999', $tester->getDisplay());
        static::assertSame(1, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY_MANY));
    }

    public function testACertificateWithoutIssueDateOrIssuingBodyIsPrintedWithEmptyCells(): void
    {
        $harness = Harness::boot();
        $harness->urssaf->respond(
            HttpScript::ROUTE_VERIFY,
            200,
            '{"verifierCodeSecuriteAttestationGen": [{"codeSecurite": "ABC", "infosSecurisation": {"nature": "AVG_UR"}}]}',
        );
        $tester = $harness->command(self::COMMAND);

        static::assertSame(0, $tester->execute(['codes' => ['ABC']]));
        static::assertSame([self::HEADER, ['ABC', 'valid', 'AVG_UR', '', '', '']], Output::rows($tester->getDisplay()));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function rejections(): iterable
    {
        yield 'expired' => ['verify_response_expired', '4W6UKDR41G457CW', self::EXPIRED_FAULT];
        yield 'unknown' => ['verify_response_unknown', 'xxx', 'FUNC202 - Attestation inconnue'];
        yield 'missing data' => ['verify_response_missing_data', 'YXDDF5V3IIZSOPS', 'FUNC203 - Donnée de sécurisation manquante'];
    }

    #[DataProvider('rejections')]
    public function testARejectedCodeFailsTheCommand(string $fixture, string $code, string $fault): void
    {
        $harness = Harness::boot();
        $harness->urssaf->respond(HttpScript::ROUTE_VERIFY, 200, Fixtures::load($fixture));
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => [$code]]));
        static::assertSame([self::HEADER, [$code, 'rejected', '', '', '', $fault]], Output::rows($tester->getDisplay()));
    }

    public function testSeveralCodesAreVerifiedInOneCall(): void
    {
        $harness = Harness::boot();
        $harness->urssaf->respond(HttpScript::ROUTE_VERIFY_MANY, 200, ManyResponses::validThenExpired());
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999', '4W6UKDR41G457CW']]));
        static::assertSame(
            [
                self::HEADER,
                ['42TROLLQU3DT999', 'valid', 'CSG_RSI', 'Urssaf AUVERGNE', '2020-10-02', ''],
                ['4W6UKDR41G457CW', 'rejected', '', '', '', self::EXPIRED_FAULT],
            ],
            Output::rows($tester->getDisplay()),
        );
        static::assertSame(1, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY_MANY));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_VERIFY));
    }

    public function testSeveralValidCodesSucceed(): void
    {
        $harness = Harness::boot();
        $harness->urssaf->respond(HttpScript::ROUTE_VERIFY_MANY, 200, ManyResponses::twoValid());
        $tester = $harness->command(self::COMMAND);

        static::assertSame(0, $tester->execute(['codes' => ['42TROLLQU3DT999', '42TROLLQU3DT999']]));
    }

    public function testTheJsonFormatListsTheNonPersonalFields(): void
    {
        $harness = Harness::boot();
        $harness->urssaf->respond(HttpScript::ROUTE_VERIFY_MANY, 200, ManyResponses::validThenExpired());
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999', '4W6UKDR41G457CW'], '--format' => 'json']));
        static::assertSame(
            [
                [
                    'code' => '42TROLLQU3DT999',
                    'valid' => true,
                    'nature' => 'CSG_RSI',
                    'issuing_body' => 'Urssaf AUVERGNE',
                    'issued_on' => '2020-10-02',
                    'fault_code' => null,
                    'fault_name' => null,
                ],
                [
                    'code' => '4W6UKDR41G457CW',
                    'valid' => false,
                    'nature' => null,
                    'issuing_body' => null,
                    'issued_on' => null,
                    'fault_code' => 'FUNC201',
                    'fault_name' => 'Attestation perimée depuis le 01/03/2019',
                ],
            ],
            json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR),
        );
        static::assertStringContainsString("\n    {\n        \"code\": \"42TROLLQU3DT999\"", $tester->getDisplay());
        static::assertStringContainsString('perimée', $tester->getDisplay());
        static::assertStringNotContainsString('Cooper', $tester->getDisplay());
    }
}
