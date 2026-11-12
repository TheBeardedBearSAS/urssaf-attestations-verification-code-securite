<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port;

/**
 * Operations of the URSSAF API. Values are the official operationIds.
 */
enum Operation: string
{
    /** GET: verifies one security code. */
    case VerifyCertificate = 'verifierCodeSecuriteAttestationGen';

    /** POST: verifies a list of security codes. */
    case VerifyCertificates = 'verifierListeCodesSecuriteAttestationGen';
}
