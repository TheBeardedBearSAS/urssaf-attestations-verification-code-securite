<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

use Psr\Log\LoggerInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\GatewayOptions;

/**
 * Container factory of the gateway options: the clock of the package and the optional logger of the application.
 */
final class GatewayOptionsFactory
{
    public static function create(Clock $clock, ?LoggerInterface $logger): GatewayOptions
    {
        $options = GatewayOptions::defaults()->withClock($clock);

        return $logger instanceof LoggerInterface ? $options->withLogger($logger) : $options;
    }
}
