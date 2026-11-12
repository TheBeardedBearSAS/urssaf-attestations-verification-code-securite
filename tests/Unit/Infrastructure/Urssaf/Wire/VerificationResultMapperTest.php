<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\CertificateNature;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\CsgDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\Details\EpmDetails;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\FaultCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\CertificateDetailsMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerificationResultMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake\CapturingLogger;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Json;

#[CoversClass(VerificationResultMapper::class)]
final class VerificationResultMapperTest extends TestCase
{
    public function testItMapsTheOfficialValidResponse(): void
    {
        $results = (new VerificationResultMapper())->map(Json::fixture('verify_response_valid'));

        static::assertCount(1, $results);
        $result = $results[0];
        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertTrue($result->isValid());
        static::assertSame('42TROLLQU3DT999', $result->securityCode()->value());
        static::assertSame('42TROLLQU3DT999', $result->hashKey());
        static::assertSame('CSG_RSI', $result->natureCode());
        static::assertSame(CertificateNature::CsgCrdsIsu, $result->nature());
        static::assertSame('Cooper Sheldon', $result->denomination());
        static::assertSame('837', $result->issuingBody()->code());
        static::assertSame('Urssaf AUVERGNE', $result->issuingBody()->label());
        static::assertSame('2020-10-02', $result->issuedOn()?->toString());
        static::assertCount(1, $result->details());
        $details = $result->details()[0];
        static::assertInstanceOf(CsgDetails::class, $details);
        static::assertSame('837000000022444999', $details->externalNumber());
        static::assertCount(1, $details->establishments());
        static::assertSame('31456972500097', $details->establishments()[0]->siret());
        static::assertSame('45 Boulevard Pasteur 63000 Clermont-Ferrand', $details->establishments()[0]->address());
    }

    /**
     * @return iterable<string, array{string, string, FaultCode, string}>
     */
    public static function rejectedFixtures(): iterable
    {
        yield 'expired' => ['verify_response_expired', '4W6UKDR41G457CW', FaultCode::Expired, 'Attestation perimée depuis le 01/03/2019'];
        yield 'unknown' => ['verify_response_unknown', 'xxx', FaultCode::Unknown, 'Attestation inconnue'];
        yield 'missing data' => ['verify_response_missing_data', 'YXDDF5V3IIZSOPS', FaultCode::MissingSecurityData, 'Donnée de sécurisation manquante'];
    }

    #[DataProvider('rejectedFixtures')]
    public function testItMapsTheOfficialFaultResponses(string $fixture, string $code, FaultCode $fault, string $name): void
    {
        $results = (new VerificationResultMapper())->map(Json::fixture($fixture));

        static::assertCount(1, $results);
        $result = $results[0];
        static::assertInstanceOf(RejectedCode::class, $result);
        static::assertFalse($result->isValid());
        static::assertSame($code, $result->securityCode()->value());
        static::assertSame($fault->value, $result->faultCode());
        static::assertSame($fault, $result->fault());
        static::assertSame($name, $result->faultName());
    }

    public function testAnEmptyOrAbsentListMapsToNothing(): void
    {
        static::assertSame([], (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => []])));
        static::assertSame([], (new VerificationResultMapper())->map(Json::payload(['other' => 1])));
    }

    public function testItKeepsTheOrderOfMixedResults(): void
    {
        // synthetic: no official example
        $results = (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => [
            ['codeSecurite' => 'B', 'functionalFaultCodeSecurite' => ['faultCode' => 'FUNC202', 'faultName' => 'n']],
            ['codeSecurite' => 'A', 'infosSecurisation' => ['hashKey' => 'A']],
        ]]));

        static::assertCount(2, $results);
        static::assertSame('B', $results[0]->securityCode()->value());
        static::assertFalse($results[0]->isValid());
        static::assertSame('A', $results[1]->securityCode()->value());
        static::assertTrue($results[1]->isValid());
    }

    public function testEveryCertificateFieldIsOptionalAndTheSecurityCodeIsTrimmed(): void
    {
        // synthetic: no official example
        $results = (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => [
            ['codeSecurite' => ' 2PTROKKQU3DTLBM ', 'infosSecurisation' => []],
        ]]));

        $result = $results[0];
        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertSame('2PTROKKQU3DTLBM', $result->securityCode()->value());
        static::assertNull($result->hashKey());
        static::assertNull($result->natureCode());
        static::assertNull($result->nature());
        static::assertNull($result->denomination());
        static::assertNull($result->issuingBody()->code());
        static::assertNull($result->issuingBody()->label());
        static::assertNull($result->issuedOn());
        static::assertSame([], $result->details());
    }

    public function testAnUnknownNatureIsKeptRaw(): void
    {
        // synthetic: no official example
        $results = (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => [
            ['codeSecurite' => 'A', 'infosSecurisation' => ['nature' => 'NEW_UR']],
        ]]));

        $result = $results[0];
        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertSame('NEW_UR', $result->natureCode());
        static::assertNull($result->nature());
    }

    public function testAnUnknownFaultCodeIsKeptRaw(): void
    {
        // synthetic: no official example
        $results = (new VerificationResultMapper())->map(Json::payload(['verifierCodeSecuriteAttestationGen' => [
            ['codeSecurite' => 'A', 'functionalFaultCodeSecurite' => []],
            ['codeSecurite' => 'B', 'functionalFaultCodeSecurite' => ['faultCode' => 'FUNC999']],
        ]]));

        static::assertInstanceOf(RejectedCode::class, $results[0]);
        static::assertNull($results[0]->faultCode());
        static::assertNull($results[0]->fault());
        static::assertNull($results[0]->faultName());
        static::assertInstanceOf(RejectedCode::class, $results[1]);
        static::assertSame('FUNC999', $results[1]->faultCode());
        static::assertNull($results[1]->fault());
    }

    public function testItDelegatesDetailsAndKeepsTheLoggerOfTheDetailsMapper(): void
    {
        // synthetic: no official example
        $logger = new CapturingLogger();
        $mapper = new VerificationResultMapper(new CertificateDetailsMapper($logger));

        $results = $mapper->map(Json::payload(['verifierCodeSecuriteAttestationGen' => [
            ['codeSecurite' => 'A', 'infosSecurisation' => ['infosSecurisationSpecifiques' => [
                ['infosSecurisationSpecifiquesEPM' => ['etablissement' => []]],
                ['infosSecurisationSpecifiquesNEW' => ['nir' => '1234567890123']],
            ]]],
        ]]));

        $result = $results[0];
        static::assertInstanceOf(VerifiedCertificate::class, $result);
        static::assertCount(1, $result->details());
        static::assertInstanceOf(EpmDetails::class, $result->details()[0]);
        static::assertCount(1, $logger->records());
        static::assertSame(['key' => 'infosSecurisationSpecifiquesNEW'], $logger->records()[0]['context']);
    }
}
