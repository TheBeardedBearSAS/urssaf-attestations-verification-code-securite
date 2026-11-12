<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf;

/**
 * Value of the `domaine` query parameter (api-reference §2.1, §3.3): ISU for self-employed craftsmen and traders,
 * URSSAF otherwise. The PDF documents it as mandatory although it is not needed, and the Swagger omits it (ADR 0005).
 */
enum VerificationDomain: string
{
    case Urssaf = 'URSSAF';
    case Isu = 'ISU';
}
