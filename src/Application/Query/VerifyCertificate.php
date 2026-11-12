<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

/**
 * Verifies one security code of a certificate for a tenant.
 */
final readonly class VerifyCertificate
{
    public function __construct(public TenantId $tenantId, public SecurityCode $securityCode) {}
}
