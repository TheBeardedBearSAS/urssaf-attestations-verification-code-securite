<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AmpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AsaaDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AvgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CaaeDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CertificateDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CfpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CsgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\EpmDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\Establishment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\FrsDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\CertificateDetailsMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Json;

// Every payload below is synthetic: no official example exists except for CSG (see VerificationResultMapperTest).
#[CoversClass(CertificateDetailsMapper::class)]
final class CertificateDetailsMapperTest extends TestCase
{
    private const ESTABLISHMENTS = [
        ['siret' => '11111111111111', 'adresse' => '1 rue A 75001 Paris'],
        ['siret' => '22222222222222', 'adresse' => '2 rue B 69001 Lyon'],
    ];

    /**
     * @param array<array-key, mixed> $specifics
     *
     * @return list<CertificateDetails>
     */
    private static function map(array $specifics, LoggerInterface $logger = new NullLogger()): array
    {
        return (new CertificateDetailsMapper($logger))->map(Json::payload($specifics)->entries());
    }

    /**
     * @param list<Establishment> $establishments
     */
    private static function assertEstablishments(array $establishments): void
    {
        static::assertCount(2, $establishments);
        static::assertSame('11111111111111', $establishments[0]->siret());
        static::assertSame('1 rue A 75001 Paris', $establishments[0]->address());
        static::assertSame('22222222222222', $establishments[1]->siret());
        static::assertSame('2 rue B 69001 Lyon', $establishments[1]->address());
    }

    public function testItMapsAmp(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesAMP' => ['siren' => '123456789', 'nir' => '1234567890123', 'etablissement' => self::ESTABLISHMENTS]]]);

        static::assertCount(1, $details);
        static::assertInstanceOf(AmpDetails::class, $details[0]);
        static::assertSame('123456789', $details[0]->siren());
        static::assertSame('1234567890123', $details[0]->nir());
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsAvg(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesAVG' => [
            'siren' => '123456789',
            'nir' => '1234567890123',
            'etablissement' => self::ESTABLISHMENTS,
            'dernierePeriodeAnalysee' => '2023-12',
            'masseSalariale' => '1500.50',
            'effectif' => '12',
        ]]]);

        static::assertCount(1, $details);
        static::assertInstanceOf(AvgDetails::class, $details[0]);
        static::assertSame('123456789', $details[0]->siren());
        static::assertSame('1234567890123', $details[0]->nir());
        static::assertSame('2023-12', $details[0]->lastAnalysedPeriod());
        static::assertSame('1500.50', $details[0]->payroll());
        static::assertSame('12', $details[0]->headcount());
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsCsg(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesCSG' => ['numExt' => '837000000000000001', 'etablissement' => self::ESTABLISHMENTS]]]);

        static::assertInstanceOf(CsgDetails::class, $details[0]);
        static::assertSame('837000000000000001', $details[0]->externalNumber());
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsCfp(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesCFP' => ['numExt' => '837000000000000002', 'etablissement' => self::ESTABLISHMENTS]]]);

        static::assertInstanceOf(CfpDetails::class, $details[0]);
        static::assertSame('837000000000000002', $details[0]->externalNumber());
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsEpm(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesEPM' => ['etablissement' => self::ESTABLISHMENTS]]]);

        static::assertInstanceOf(EpmDetails::class, $details[0]);
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsFrs(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesFRS' => ['etablissement' => self::ESTABLISHMENTS]]]);

        static::assertInstanceOf(FrsDetails::class, $details[0]);
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsCaae(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesCAAE' => ['etablissement' => self::ESTABLISHMENTS]]]);

        static::assertInstanceOf(CaaeDetails::class, $details[0]);
        self::assertEstablishments($details[0]->establishments());
    }

    public function testItMapsAsaa(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesASAA' => [
            'nomArtisteAuteur' => 'Dupont',
            'prenomArtisteAuteur' => 'Marie',
            'dateDebutDeclaration' => '2023-01-01T00:00:00Z',
            'dateFinDeclaration' => '2023-12-31T23:59:59+01:00',
        ]]]);

        static::assertInstanceOf(AsaaDetails::class, $details[0]);
        static::assertSame('Dupont', $details[0]->lastName());
        static::assertSame('Marie', $details[0]->firstName());
        static::assertSame('2023-01-01T00:00:00Z', $details[0]->declarationStart()?->toIso8601());
        static::assertSame('2023-12-31T22:59:59Z', $details[0]->declarationEnd()?->toIso8601());
    }
}
