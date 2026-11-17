<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;

/**
 * The options of the console commands (--tenant, --format) and their reading.
 */
final class CommandOptions
{
    public const FORMAT_TABLE = 'table';
    public const FORMAT_JSON = 'json';

    public static function addTenant(Command $command, string $description): void
    {
        $command->addOption('tenant', null, InputOption::VALUE_REQUIRED, $description);
    }

    public static function addFormat(Command $command): void
    {
        $command->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json',
            self::FORMAT_TABLE,
        );
    }

    /**
     * @throws InvalidOptionException for a format other than "table" or "json"
     */
    public static function format(InputInterface $input): string
    {
        $format = self::text($input, 'format') ?? self::FORMAT_TABLE;
        if ($format !== self::FORMAT_TABLE && $format !== self::FORMAT_JSON) {
            throw new InvalidOptionException('The --format option must be "table" or "json".');
        }

        return $format;
    }

    /**
     * The tenant given with --tenant, else the only configured tenant.
     *
     * @param list<string> $configured
     *
     * @throws InvalidOptionException when no tenant can be determined
     */
    public static function tenantOrSingleConfigured(InputInterface $input, array $configured): TenantId
    {
        $tenant = self::text($input, 'tenant');
        if ($tenant !== null) {
            return TenantId::fromString($tenant);
        }
        if (\count($configured) !== 1) {
            throw new InvalidOptionException(
                'The --tenant option is required unless exactly one tenant is configured ("urssaf_attestations_verification_code_securite.tenants").',
            );
        }

        return TenantId::fromString($configured[0]);
    }

    /**
     * The tenant given with --tenant, else the configured tenants.
     *
     * @param list<string> $configured
     *
     * @return non-empty-list<TenantId>
     *
     * @throws InvalidOptionException when no tenant can be determined
     */
    public static function tenantsOrConfigured(InputInterface $input, array $configured): array
    {
        $tenant = self::text($input, 'tenant');
        if ($tenant !== null) {
            return [TenantId::fromString($tenant)];
        }
        $tenants = array_map(TenantId::fromString(...), $configured);

        return $tenants === [] ? throw new InvalidOptionException('No tenant to check: use --tenant=<id> or configure "urssaf_attestations_verification_code_securite.tenants".') : $tenants;
    }

    private static function text(InputInterface $input, string $name): ?string
    {
        // @mago-expect analysis:mixed-assignment Symfony options are untyped: the value is narrowed on the next line.
        $value = $input->getOption($name);

        return \is_string($value) ? $value : null;
    }
}
