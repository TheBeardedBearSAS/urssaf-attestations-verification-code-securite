<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf;

/**
 * Target URSSAF platform. The sandbox ("environnement Éditeur") host must stay overridable by configuration.
 */
enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function baseUri(): string
    {
        return match ($this) {
            self::Sandbox => 'https://api-edi.urssaf.fr',
            self::Production => 'https://api.urssaf.fr',
        };
    }
}
