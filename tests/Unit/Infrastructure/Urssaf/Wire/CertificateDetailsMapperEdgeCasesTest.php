<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AmpDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\AsaaDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CaaeDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CertificateDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\EpmDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\FrsDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\CertificateDetailsMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\CapturingLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Json;

// Every payload below is synthetic: no official example exists except for CSG (see VerificationResultMapperTest).
#[CoversClass(CertificateDetailsMapper::class)]
final class CertificateDetailsMapperEdgeCasesTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $specifics
     *
     * @return list<CertificateDetails>
     */
    private static function map(array $specifics, LoggerInterface $logger = new NullLogger()): array
    {
        return (new CertificateDetailsMapper($logger))->map(Json::payload($specifics)->entries());
    }

    public function testItMapsNothingToNothing(): void
    {
        static::assertSame([], self::map([]));
    }

    public function testAsaaDatesAreOptional(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesASAA' => ['nomArtisteAuteur' => 'Dupont']]]);

        static::assertInstanceOf(AsaaDetails::class, $details[0]);
        static::assertNull($details[0]->firstName());
        static::assertNull($details[0]->declarationStart());
        static::assertNull($details[0]->declarationEnd());
    }

    public function testAnInvalidAsaaDateIsRejected(): void
    {
        // synthetic: no official example
        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage('date-time is not a valid RFC 3339 value');

        self::map([['infosSecurisationSpecifiquesASAA' => ['dateFinDeclaration' => '31/12/2023']]]);
    }

    public function testMissingFieldsAndEstablishmentsAreNull(): void
    {
        // synthetic: no official example
        $details = self::map([['infosSecurisationSpecifiquesAMP' => ['etablissement' => [[]]]]]);

        static::assertInstanceOf(AmpDetails::class, $details[0]);
        static::assertNull($details[0]->siren());
        static::assertNull($details[0]->nir());
        static::assertCount(1, $details[0]->establishments());
        static::assertNull($details[0]->establishments()[0]->siret());
        static::assertNull($details[0]->establishments()[0]->address());
    }

    public function testItKeepsEveryKeyOfEveryItemInOrder(): void
    {
        // synthetic: no official example
        $details = self::map([
            ['infosSecurisationSpecifiquesEPM' => ['etablissement' => []], 'infosSecurisationSpecifiquesFRS' => ['etablissement' => []]],
            ['infosSecurisationSpecifiquesCAAE' => ['etablissement' => []]],
        ]);

        static::assertCount(3, $details);
        static::assertInstanceOf(EpmDetails::class, $details[0]);
        static::assertInstanceOf(FrsDetails::class, $details[1]);
        static::assertInstanceOf(CaaeDetails::class, $details[2]);
    }

    public function testAnUnknownKeyIsIgnoredAndOnlyItsNameIsLogged(): void
    {
        // synthetic: no official example
        $logger = new CapturingLogger();

        $details = self::map([
            ['infosSecurisationSpecifiquesXYZ' => ['nir' => '1234567890123', 'siren' => '123456789']],
            ['infosSecurisationSpecifiquesFRS' => ['etablissement' => []], 'other' => ['nir' => '1234567890123']],
            ['type' => '1234567890123', 'empty' => null],
        ], $logger);

        static::assertCount(1, $details);
        static::assertInstanceOf(FrsDetails::class, $details[0]);
        static::assertSame(
            [
                ['level' => 'warning', 'message' => 'Unknown URSSAF certificate details key ignored', 'context' => ['key' => 'infosSecurisationSpecifiquesXYZ']],
                ['level' => 'warning', 'message' => 'Unknown URSSAF certificate details key ignored', 'context' => ['key' => 'other']],
                ['level' => 'warning', 'message' => 'Unknown URSSAF certificate details key ignored', 'context' => ['key' => 'type']],
                ['level' => 'warning', 'message' => 'Unknown URSSAF certificate details key ignored', 'context' => ['key' => 'empty']],
            ],
            $logger->records(),
        );
        static::assertStringNotContainsString('1234567890123', $logger->dump());
        static::assertStringNotContainsString('123456789', $logger->dump());
    }

    public function testAKnownKeyWithANullValueIsSkippedWithoutLogging(): void
    {
        $logger = new CapturingLogger();

        static::assertSame([], self::map([['infosSecurisationSpecifiquesAMP' => null]], $logger));
        static::assertCount(1, self::map([['infosSecurisationSpecifiquesAMP' => null, 'infosSecurisationSpecifiquesEPM' => ['etablissement' => []]]], $logger));
        static::assertSame([], $logger->records());
    }

    public function testAKeyWithAScalarValueIsRejected(): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage('field "infosSecurisationSpecifiquesAMP" is not an object');

        self::map([['infosSecurisationSpecifiquesAMP' => 'text']]);
    }

    public function testAnEstablishmentListOfScalarsIsRejected(): void
    {
        $this->expectException(UnexpectedUrssafResponse::class);

        self::map([['infosSecurisationSpecifiquesEPM' => ['etablissement' => ['text']]]]);
    }
}
