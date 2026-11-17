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

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;

/**
 * Validation predicates of the configuration tree (each one is true when the value is invalid).
 */
final class ConfigurationRules
{
    /**
     * @param array<array-key, mixed> $value
     */
    public static function hasBothCredentialSources(array $value): bool
    {
        /** @var array{credentials: array<array-key, mixed>, credentials_provider: ?string} $config */
        $config = $value;

        return $config['credentials'] !== [] && $config['credentials_provider'] !== null;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    public static function hasNoCredentialSource(array $value): bool
    {
        /** @var array{credentials: array<array-key, mixed>, credentials_provider: ?string} $config */
        $config = $value;

        return $config['credentials'] === [] && $config['credentials_provider'] === null;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    public static function simulatedGatewayInProduction(array $value): bool
    {
        /** @var array{environment: string, gateway: array{simulated: bool}} $config */
        $config = $value;

        return $config['gateway']['simulated'] && $config['environment'] === 'production';
    }

    /**
     * The empty string is the dummy value of an environment variable placeholder while the container is validated:
     * an empty variable fails at runtime instead (Environment::from()).
     */
    public static function isUnknownEnvironment(mixed $environment): bool
    {
        return !\in_array($environment, ['', ...array_map(static fn(Environment $case): string => $case->value, Environment::cases())], true);
    }
}
