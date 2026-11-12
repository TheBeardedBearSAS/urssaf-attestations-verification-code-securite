<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Wire;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\DateEncoding;

#[CoversClass(DateEncoding::class)]
final class DateEncodingTest extends TestCase
{
    public function testItDecodesAnIssueDate(): void
    {
        static::assertSame('2020-10-02', DateEncoding::decodeIssueDate('02/10/2020')->toString());
        static::assertSame('2020-02-29', DateEncoding::decodeIssueDate('29/02/2020')->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIssueDates(): iterable
    {
        yield 'day not zero padded' => ['2/10/2020'];
        yield 'month not zero padded' => ['02/1/2020'];
        yield 'day overflow' => ['31/02/2020'];
        yield 'non leap year' => ['29/02/2021'];
        yield 'iso format' => ['2020-10-02'];
        yield 'two digits year' => ['02/10/20'];
        yield 'trailing data' => ['02/10/2020 10:00'];
        yield 'leading space' => [' 02/10/2020'];
        yield 'empty' => [''];
        yield 'text' => ['not-a-date'];
        yield 'year zero' => ['01/01/0000'];
    }

    #[DataProvider('invalidIssueDates')]
    public function testItRejectsAnythingButStrictDayMonthYear(string $value): void
    {
        try {
            DateEncoding::decodeIssueDate($value);
            static::fail('Expected an UnexpectedUrssafResponse.');
        } catch (UnexpectedUrssafResponse $exception) {
            static::assertStringStartsWith('Unexpected URSSAF response: issue date is not a valid DD/MM/YYYY date', $exception->getMessage());
            static::assertStringNotContainsString($value === '' ? 'never' : $value, $exception->getMessage());
        }
    }

    public function testItDecodesAnInstant(): void
    {
        static::assertSame('2022-02-01T15:40:00Z', DateEncoding::decodeInstant('2022-02-01T16:40:00+01:00')->toIso8601());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidInstants(): iterable
    {
        yield 'no offset' => ['2022-02-01T16:40:00'];
        yield 'non existing date' => ['2022-02-31T16:40:00Z'];
        yield 'text' => ['yesterday'];
    }

    #[DataProvider('invalidInstants')]
    public function testItWrapsInvalidInstants(string $value): void
    {
        try {
            DateEncoding::decodeInstant($value);
            static::fail('Expected an UnexpectedUrssafResponse.');
        } catch (UnexpectedUrssafResponse $exception) {
            static::assertSame('Unexpected URSSAF response: date-time is not a valid RFC 3339 value.', $exception->getMessage());
            static::assertNotNull($exception->getPrevious());
        }
    }
}
