<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic;

/**
 * Outcome of one installation check; only a failure makes `urssaf:check` exit with 1.
 */
enum CheckStatus: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Failure = 'failure';
    case Skipped = 'skipped';
}
