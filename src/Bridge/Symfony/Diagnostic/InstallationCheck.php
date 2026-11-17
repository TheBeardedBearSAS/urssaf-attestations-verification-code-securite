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
 * A check run by `urssaf:check`. Services tagged "urssaf_attestations_verification_code_securite.installation_check"
 * (autoconfigured) run after the checks of the package, unless their tag has a priority of 1 or more: the checks of the
 * package have a priority of 1, so a tag priority of 1 or more puts the checks of the host before them.
 */
interface InstallationCheck
{
    public const TAG = 'urssaf_attestations_verification_code_securite.installation_check';

    /**
     * @return list<CheckResult>
     */
    public function run(CheckContext $context): array;
}
