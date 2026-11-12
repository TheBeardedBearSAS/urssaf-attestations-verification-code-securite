<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificatesHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Application\Fake\FakeCertificateVerificationGateway;
use Throwable;

#[CoversClass(VerifyCertificates::class)]
#[CoversClass(VerifyCertificatesHandler::class)]
final class VerifyCertificatesHandlerTest extends TestCase
{
    public function testEmptyInputMakesNoCall(): void
    {
        $gateway = self::echoingGateway();

        static::assertSame([], (new VerifyCertificatesHandler($gateway))(new VerifyCertificates(self::tenant())));
        static::assertSame([], $gateway->batches());
    }

    /**
     * @param list<int> $expectedBatchSizes
     */
    #[DataProvider('counts')]
    public function testCodesAreChunkedInOrder(int $count, array $expectedBatchSizes): void
    {
        $codes = self::codes($count);
        $gateway = self::echoingGateway();

        $results = (new VerifyCertificatesHandler($gateway))(new VerifyCertificates(self::tenant(), ...$codes));

        static::assertSame($expectedBatchSizes, array_map(count(...), $gateway->batches()));
        static::assertSame($codes, array_merge(...$gateway->batches()));
        static::assertSame($codes, array_map(static fn(VerificationResult $r): SecurityCode => $r->securityCode(), $results));
    }

    /**
     * @return iterable<string, array{int, list<int>}>
     */
    public static function counts(): iterable
    {
        yield 'one' => [1, [1]];
        yield 'exactly 100' => [100, [100]];
        yield '101' => [101, [100, 1]];
        yield '250' => [250, [100, 100, 50]];
    }

    public function testDuplicatesAreKeptAsGiven(): void
    {
        $a = SecurityCode::fromString('A');
        $b = SecurityCode::fromString('B');
        $gateway = self::echoingGateway();

        $results = (new VerifyCertificatesHandler($gateway))(new VerifyCertificates(self::tenant(), $a, $b, $a));

        static::assertSame([[$a, $b, $a]], $gateway->batches());
        static::assertSame([$a, $b, $a], array_map(static fn(VerificationResult $r): SecurityCode => $r->securityCode(), $results));
    }

    public function testQueryReindexesCodesAndKeepsTenant(): void
    {
        $a = SecurityCode::fromString('A');
        $b = SecurityCode::fromString('B');

        $query = new VerifyCertificates(self::tenant(), ...['x' => $a, 'y' => $b]);

        static::assertSame([$a, $b], $query->securityCodes);
        static::assertSame('tenant-1', $query->tenantId->value());
    }

    public function testFailureOfASecondChunkPropagates(): void
    {
        $failure = new RuntimeException('second chunk');
        $calls = 0;
        $gateway = new FakeCertificateVerificationGateway();
        $gateway->willVerifyManyWith(static function (array $codes) use (&$calls, $failure): array|Throwable {
            ++$calls;

            return $calls === 2 ? $failure : self::resultsFor($codes);
        });

        try {
            (new VerifyCertificatesHandler($gateway))(new VerifyCertificates(self::tenant(), ...self::codes(250)));
            static::fail('Expected exception');
        } catch (RuntimeException $exception) {
            static::assertSame($failure, $exception);
        }

        static::assertSame([100, 100], array_map(count(...), $gateway->batches()));
    }

    private static function tenant(): TenantId
    {
        return TenantId::fromString('tenant-1');
    }

    /**
     * @return list<SecurityCode>
     */
    private static function codes(int $count): array
    {
        $codes = [];
        for ($i = 0; $i < $count; ++$i) {
            $codes[] = SecurityCode::fromString('CODE' . $i);
        }

        return $codes;
    }

    /**
     * @param list<SecurityCode> $codes
     *
     * @return list<VerificationResult>
     */
    private static function resultsFor(array $codes): array
    {
        return array_map(
            static fn(SecurityCode $code): VerificationResult => RejectedCode::of($code, null, null),
            $codes,
        );
    }

    private static function echoingGateway(): FakeCertificateVerificationGateway
    {
        $gateway = new FakeCertificateVerificationGateway();
        $gateway->willVerifyManyWith(self::resultsFor(...));

        return $gateway;
    }
}
