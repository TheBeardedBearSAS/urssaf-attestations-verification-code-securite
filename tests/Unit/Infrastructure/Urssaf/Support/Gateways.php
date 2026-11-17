<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafHttpGateway;

final class Gateways
{
    private function __construct() {}

    public static function on(Stack $stack): UrssafHttpGateway
    {
        return new UrssafHttpGateway($stack->credentials, $stack->jsonClient(), $stack->configuration);
    }

    public const REQUEST_FIXTURE_CODES = [
        'KZHVYO7KP3JGXNL',
        'R9ES9TEO7IZNIRA',
        'AMOHWO6UPE8MQL5',
        'VZY816V353A73KE',
        'XTROQW5FLMBNZWB',
        '2WKNZFOUTWUWM56',
        'OPP1QIM6MB5SRLW',
        '5B1UXK5U3P4OWKX',
        '4W6UKDR41G457CW',
    ];

    /**
     * @param list<string> $values
     *
     * @return list<SecurityCode>
     */
    public static function codes(array $values): array
    {
        return array_map(SecurityCode::fromString(...), $values);
    }
}
