<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckContext;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\InstallationCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;

/**
 * A host check that raises a package failure for the tenant "tenant-throws" and reports nothing otherwise.
 */
final class ThrowingCheck implements InstallationCheck
{
    public const TENANT = 'tenant-throws';

    public function run(CheckContext $context): array
    {
        foreach ($context->tenants as $tenant) {
            if ($tenant->value() === self::TENANT) {
                throw InvariantViolation::because('host.check_broken', 'The host check is broken.');
            }
        }

        return [];
    }
}
