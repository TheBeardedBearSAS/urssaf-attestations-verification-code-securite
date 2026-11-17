<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Fake;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\IdGenerator;

final class SequenceIdGenerator implements IdGenerator
{
    private int $counter = 0;

    /**
     * @return non-empty-string
     */
    public function generate(): string
    {
        ++$this->counter;

        return 'request-id-' . $this->counter;
    }
}
