<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

final class StaticCredentialsProvider implements CredentialsProvider
{
    public const CLIENT_ID = 'fictitious-client-id';
    public const SHARED_VALUE = 'fictitious-client-secret-value';

    public function forTenant(TenantId $tenantId): Credentials
    {
        return Credentials::of(self::CLIENT_ID, self::SHARED_VALUE);
    }
}
