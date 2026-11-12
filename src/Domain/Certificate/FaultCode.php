<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate;

/**
 * Functional fault of a rejected security code (wire "functionalFaultCodeSecurite.faultCode").
 */
enum FaultCode: string
{
    case Expired = 'FUNC201';
    case Unknown = 'FUNC202';
    case MissingSecurityData = 'FUNC203';
}
