<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use JsonException;

/**
 * Minimal JSON encoder for request bodies made of strings only (lists of strings, nested arrays of strings).
 *
 * A PHP list (including the empty array) is encoded as a JSON array and any other array as an object.
 */
final class JsonEncoder
{
    private const FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param array<array-key, array<array-key, mixed>|string> $value
     *
     * @throws JsonException for invalid UTF-8 strings
     */
    public static function encode(array $value): string
    {
        return \strval(json_encode($value, self::FLAGS));
    }
}
