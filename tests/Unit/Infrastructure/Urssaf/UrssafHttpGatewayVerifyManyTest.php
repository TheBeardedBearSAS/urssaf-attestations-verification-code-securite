<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Gateways;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Stacks;

#[CoversClass(UrssafHttpGateway::class)]
final class UrssafHttpGatewayVerifyManyTest extends TestCase
{
    /**
     * @param list<string> $codes
     */
    private static function responseFor(array $codes): string
    {
        $items = [];
        foreach ($codes as $index => $code) {
            $items[] = $index % 2 === 0
                ? ['codeSecurite' => $code, 'infosSecurisation' => ['nature' => 'CSG_RSI']]
                : ['codeSecurite' => $code, 'functionalFaultCodeSecurite' => ['faultCode' => 'FUNC202', 'faultName' => 'Inconnue']];
        }

        return json_encode(['verifierCodeSecuriteAttestationGen' => $items], JSON_THROW_ON_ERROR);
    }

    public function testNineCodesYieldNineResultsInResponseOrder(): void
    {
        $codes = Gateways::REQUEST_FIXTURE_CODES;
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, self::responseFor($codes));

        $results = Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes($codes));

        static::assertCount(9, $results);
        foreach ($results as $index => $result) {
            static::assertSame($codes[$index], $result->securityCode()->value());
            static::assertInstanceOf($index % 2 === 0 ? VerifiedCertificate::class : RejectedCode::class, $result);
        }
    }

    public function testTheRequestIsAPostOfTheOfficialBodyWithoutDomain(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, self::responseFor(Gateways::REQUEST_FIXTURE_CODES));

        Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes(Gateways::REQUEST_FIXTURE_CODES));

        $request = $stack->lastRequest();
        static::assertSame('POST', $request->getMethod());
        static::assertSame(
            'https://api-edi.urssaf.fr/attn/verification/v1/codes_securite/verifier',
            (string) $request->getUri(),
        );
        static::assertEquals(
            json_decode(Fixtures::load('verify_many_request'), true, 512, JSON_THROW_ON_ERROR),
            json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
        static::assertStringStartsWith('{"listeCodesSecurite":["KZHVYO7KP3JGXNL","R9ES9TEO7IZNIRA"', (string) $request->getBody());
    }

    public function testDuplicatesAreSentAsGiven(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, self::responseFor(['A', 'A']));

        Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes(['A', 'A']));

        static::assertSame('{"listeCodesSecurite":["A","A"]}', (string) $stack->lastRequest()->getBody());
    }

    public function testTheMaximumNumberOfCodesIsAccepted(): void
    {
        $codes = array_fill(0, CertificateVerificationGateway::MAX_CODES_PER_CALL, 'A');
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, self::responseFor($codes));

        static::assertCount(100, Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes($codes)));
    }

    public function testASingleCodeIsAccepted(): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, self::responseFor(['A']));

        static::assertCount(1, Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes(['A'])));
    }

    /**
     * @return iterable<string, array{non-negative-int}>
     */
    public static function outOfRangeCounts(): iterable
    {
        yield 'none' => [0];
        yield 'one above the maximum' => [101];
    }

    /**
     * @param non-negative-int $count
     */
    #[DataProvider('outOfRangeCounts')]
    public function testAnOutOfRangeCountIsRejectedBeforeAnyRequest(int $count): void
    {
        $stack = Stacks::sandbox();
        $gateway = Gateways::on($stack);

        try {
            $gateway->verifyMany(Stacks::tenant(), ...Gateways::codes(array_fill(0, $count, 'A')));
            static::fail('Expected an invariant violation.');
        } catch (InvariantViolation $exception) {
            static::assertSame('security_codes.count_out_of_range', $exception->violationCode());
            static::assertSame('The number of security codes must be between 1 and 100.', $exception->getMessage());
            static::assertSame(['count' => $count, 'min' => 1, 'max' => 100], $exception->params());
        }

        static::assertSame([], $stack->requests());
    }

    /**
     * @return iterable<string, array{non-negative-int, string}>
     */
    public static function mismatchedCounts(): iterable
    {
        yield 'fewer' => [1, 'Unexpected URSSAF response: expected 2 results, got 1.'];
        yield 'more' => [3, 'Unexpected URSSAF response: expected 2 results, got 3.'];
    }

    /**
     * @param non-negative-int $answered
     */
    #[DataProvider('mismatchedCounts')]
    public function testAResponseWithAnotherCountIsUnexpected(int $answered, string $message): void
    {
        $stack = Stacks::sandbox();
        $stack->respondToken();
        $stack->respond(200, self::responseFor(array_fill(0, $answered, 'A')));

        $this->expectException(UnexpectedUrssafResponse::class);
        $this->expectExceptionMessage($message);

        Gateways::on($stack)->verifyMany(Stacks::tenant(), ...Gateways::codes(['A', 'B']));
    }
}
