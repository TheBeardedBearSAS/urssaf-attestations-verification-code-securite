<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

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
