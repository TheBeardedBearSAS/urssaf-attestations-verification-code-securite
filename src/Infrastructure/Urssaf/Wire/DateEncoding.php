<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use DateTimeImmutable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\CalendarDate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\Instant;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;

/**
 * Wire formats of the dates: the issue date is `d/m/Y` (api-reference section 2.1), the ASAA declaration bounds are RFC 3339.
 */
final class DateEncoding
{
    private const FORMAT = 'd/m/Y';
    private const PARSE_FORMAT = '!d/m/Y';

    /**
     * Strict `d/m/Y`: "31/02/2020" and "2/10/2020" are rejected.
     *
     * @throws UnexpectedUrssafResponse
     */
    public static function decodeIssueDate(string $value): CalendarDate
    {
        $parsed = DateTimeImmutable::createFromFormat(self::PARSE_FORMAT, $value);

        if ($parsed === false || $parsed->format(self::FORMAT) !== $value) {
            throw UnexpectedUrssafResponse::invalidPayload('issue date is not a valid DD/MM/YYYY date');
        }

        try {
            return CalendarDate::fromString($parsed->format('Y-m-d'));
        } catch (InvalidValue $exception) {
            throw UnexpectedUrssafResponse::causedBy('issue date is not a valid DD/MM/YYYY date', $exception);
        }
    }

    /**
     * @throws UnexpectedUrssafResponse
     */
    public static function decodeInstant(string $value): Instant
    {
        try {
            return Instant::fromIso8601($value);
        } catch (InvalidValue $exception) {
            throw UnexpectedUrssafResponse::causedBy('date-time is not a valid RFC 3339 value', $exception);
        }
    }
}
