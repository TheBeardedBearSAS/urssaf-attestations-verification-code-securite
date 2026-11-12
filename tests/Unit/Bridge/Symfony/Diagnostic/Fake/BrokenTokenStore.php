<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Bridge\Symfony\Diagnostic\Fake;

use LogicException;
use SensitiveParameter;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\AccessToken;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;

/**
 * An unavailable store: every operation throws, with a message that must never be reported.
 */
final class BrokenTokenStore implements TokenStore
{
    public function get(string $key): ?AccessToken
    {
        throw new LogicException('redis://user:password@host');
    }

    public function save(string $key, #[SensitiveParameter] AccessToken $token): void
    {
        throw new LogicException('redis://user:password@host');
    }

    public function delete(string $key): void
    {
        throw new LogicException('redis://user:password@host');
    }
}
