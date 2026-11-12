<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\UrssafException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

/**
 * Outbound port to the URSSAF certificate security code verification API.
 *
 * Every method may throw {@see UrssafRequestRejected}, {@see UrssafAuthenticationFailed}, {@see UrssafUnavailable} or
 * another {@see UrssafException}.
 */
interface CertificateVerificationGateway
{
    /** Maximum number of codes accepted by one call of {@see self::verifyMany()}. */
    public const MAX_CODES_PER_CALL = 100;

    /**
     * Verifies one security code (GET verifierCodeSecuriteAttestationGen).
     *
     * @throws UrssafException
     */
    public function verify(TenantId $tenantId, SecurityCode $securityCode): VerificationResult;

    /**
     * Verifies 1 to {@see self::MAX_CODES_PER_CALL} security codes in one call (POST
     * verifierListeCodesSecuriteAttestationGen). Results are in response order, one per code.
     *
     * @return list<VerificationResult>
     *
     * @throws UrssafException {@see InvariantViolation} when given no code or more than
     *                         {@see self::MAX_CODES_PER_CALL} codes, otherwise as for {@see self::verify()}
     */
    public function verifyMany(TenantId $tenantId, SecurityCode ...$securityCodes): array;
}
