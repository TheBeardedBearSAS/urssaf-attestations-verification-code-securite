<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port;

/**
 * Generates unique identifiers (UUID v4 recommended) for x-request-id headers.
 */
interface IdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}
