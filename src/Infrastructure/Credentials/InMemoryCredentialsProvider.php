<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\RecordNotFound;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

/**
 * Credentials provider backed by a map of tenant identifier to credentials (configuration or tests).
 */
final readonly class InMemoryCredentialsProvider implements CredentialsProvider
{
    /**
     * @param array<string, Credentials> $credentialsByTenant keyed by tenant identifier
     */
    public function __construct(private array $credentialsByTenant) {}

    /**
     * @throws RecordNotFound "credentials.not_found"
     */
    public function forTenant(TenantId $tenantId): Credentials
    {
        return $this->credentialsByTenant[$tenantId->value()]
            ?? throw RecordNotFound::withIdentifier('credentials', $tenantId->value());
    }
}
