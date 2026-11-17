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
 * Generates unique identifiers (UUID v4 recommended) for x-request-id headers.
 */
interface IdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}
