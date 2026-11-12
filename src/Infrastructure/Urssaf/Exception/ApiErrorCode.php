<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception;

/**
 * Request-level error codes of the URSSAF API (api-reference sections 1.3 and 3.2, ADR 0006). Values are the exact
 * wire codes. Per-code faults (FUNC201..FUNC203) belong to the domain.
 *
 * Unknown codes are legitimate (URSSAF may add some): use {@see self::fromCode()} which returns null for them.
 */
enum ApiErrorCode: string
{
    /** Service call failure; today also sent when more than 100 codes are submitted. */
    case ServiceCallFailed = 'FUNC013';

    /** The security code is missing. */
    case SecurityCodeRequired = 'FUNC015';

    /** More than 100 codes in one call (announced replacement of FUNC013, PDF section 3.2 footnote 2). */
    case TooManySecurityCodes = 'FUNC016';

    /** Technical error (HTTP 500). */
    case TechnicalError = 'ERR_TECHNIQUE';

    /**
     * Normalises a raw wire code: surrounding or inner whitespace is dropped.
     */
    public static function normalise(string $rawCode): string
    {
        return preg_replace('/\s+/', '', $rawCode) ?? $rawCode;
    }

    public static function fromCode(string $rawCode): ?self
    {
        return self::tryFrom(self::normalise($rawCode));
    }

    /**
     * Technical error (HTTP 500): retryable.
     */
    public function isTechnical(): bool
    {
        return $this === self::TechnicalError;
    }
}
