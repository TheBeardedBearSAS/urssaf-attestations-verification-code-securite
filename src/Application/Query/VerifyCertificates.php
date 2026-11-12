<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

/**
 * Verifies any number of security codes for a tenant (the handler splits them into calls the API accepts).
 */
final readonly class VerifyCertificates
{
    /** @var list<SecurityCode> */
    public array $securityCodes;

    public function __construct(public TenantId $tenantId, SecurityCode ...$securityCodes)
    {
        $this->securityCodes = array_values($securityCodes);
    }
}
