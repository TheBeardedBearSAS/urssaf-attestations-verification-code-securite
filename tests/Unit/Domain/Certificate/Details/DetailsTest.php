<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Domain\Certificate\Details;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AmpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AsaaDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AvgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CaaeDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CfpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CsgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\EpmDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\Establishment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\FrsDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\Instant;

#[CoversClass(AmpDetails::class)]
#[CoversClass(AvgDetails::class)]
#[CoversClass(CsgDetails::class)]
#[CoversClass(CfpDetails::class)]
#[CoversClass(EpmDetails::class)]
#[CoversClass(FrsDetails::class)]
#[CoversClass(CaaeDetails::class)]
#[CoversClass(AsaaDetails::class)]
#[CoversClass(Establishment::class)]
final class DetailsTest extends TestCase
{
    public function testEstablishment(): void
    {
        $establishment = Establishment::of('12345678901234', '1 rue X');
        static::assertSame('12345678901234', $establishment->siret());
        static::assertSame('1 rue X', $establishment->address());
        static::assertNull(Establishment::of(null, null)->siret());
        static::assertNull(Establishment::of(null, null)->address());
    }

    public function testAmp(): void
    {
        $establishments = [Establishment::of('1', 'a')];
        $details = AmpDetails::of('siren', 'nir', $establishments);

        static::assertSame('siren', $details->siren());
        static::assertSame('nir', $details->nir());
        static::assertSame($establishments, $details->establishments());
        static::assertSame(
            ['siren' => 'siren', 'nir' => '***', 'establishments' => $establishments],
            $details->__debugInfo(),
        );
        static::assertSame(
            ['siren' => null, 'nir' => null, 'establishments' => []],
            AmpDetails::of(null, null, [])->__debugInfo(),
        );
    }

    public function testAvg(): void
    {
        $establishments = [Establishment::of('1', 'a')];
        $details = AvgDetails::of('siren', 'nir', $establishments, '2020', '100', '5');

        static::assertSame('siren', $details->siren());
        static::assertSame('nir', $details->nir());
        static::assertSame($establishments, $details->establishments());
        static::assertSame('2020', $details->lastAnalysedPeriod());
        static::assertSame('100', $details->payroll());
        static::assertSame('5', $details->headcount());
        static::assertSame(
            [
                'siren' => 'siren',
                'nir' => '***',
                'establishments' => $establishments,
                'lastAnalysedPeriod' => '2020',
                'payroll' => '100',
                'headcount' => '5',
            ],
            $details->__debugInfo(),
        );
        static::assertNull(AvgDetails::of(null, null, [], null, null, null)->__debugInfo()['nir']);
    }

    public function testCsgAndCfp(): void
    {
        $establishments = [Establishment::of('1', 'a')];
        $csg = CsgDetails::of('ext', $establishments);
        $cfp = CfpDetails::of('ext2', $establishments);

        static::assertSame('ext', $csg->externalNumber());
        static::assertSame($establishments, $csg->establishments());
        static::assertSame('ext2', $cfp->externalNumber());
        static::assertSame($establishments, $cfp->establishments());
    }

    public function testEstablishmentOnlyDetails(): void
    {
        $establishments = [Establishment::of('1', 'a')];

        foreach ([EpmDetails::of($establishments), FrsDetails::of($establishments), CaaeDetails::of($establishments)] as $details) {
            static::assertSame($establishments, $details->establishments());
        }
    }

    public function testAsaa(): void
    {
        $start = Instant::fromIso8601('2020-01-01T00:00:00Z');
        $end = Instant::fromIso8601('2020-12-31T00:00:00Z');
        $details = AsaaDetails::of('Doe', 'Jane', $start, $end);

        static::assertSame('Doe', $details->lastName());
        static::assertSame('Jane', $details->firstName());
        static::assertSame($start, $details->declarationStart());
        static::assertSame($end, $details->declarationEnd());
        static::assertSame(
            ['lastName' => '***', 'firstName' => '***', 'declarationStart' => $start, 'declarationEnd' => $end],
            $details->__debugInfo(),
        );
        static::assertSame(
            ['lastName' => null, 'firstName' => null, 'declarationStart' => null, 'declarationEnd' => null],
            AsaaDetails::of(null, null, null, null)->__debugInfo(),
        );
    }
}
