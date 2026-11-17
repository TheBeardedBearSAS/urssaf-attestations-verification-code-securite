<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

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
