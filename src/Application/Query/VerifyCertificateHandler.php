<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;

final readonly class VerifyCertificateHandler
{
    public function __construct(private CertificateVerificationGateway $gateway) {}

    public function __invoke(VerifyCertificate $query): VerificationResult
    {
        return $this->gateway->verify($query->tenantId, $query->securityCode);
    }
}
