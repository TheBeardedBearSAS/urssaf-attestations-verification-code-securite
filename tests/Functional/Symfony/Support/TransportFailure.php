<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support;

use LogicException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * A PSR-18 failure (connection refused...).
 */
final class TransportFailure extends LogicException implements ClientExceptionInterface
{
    public function __construct()
    {
        parent::__construct('connection refused');
    }
}
