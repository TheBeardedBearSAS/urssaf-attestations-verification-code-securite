<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Bridge\Symfony\Diagnostic\Fake;

use SensitiveParameter;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\AccessToken;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\TokenStore;

/**
 * A faulty store that forgets everything.
 */
final class AmnesicTokenStore implements TokenStore
{
    public function get(string $key): ?AccessToken
    {
        return null;
    }

    public function save(string $key, #[SensitiveParameter] AccessToken $token): void {}

    public function delete(string $key): void {}
}
