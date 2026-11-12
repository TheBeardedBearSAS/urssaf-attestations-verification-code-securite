<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;

final class BundleTenantsTest extends TestCase
{
    public function testTheTenantsAreTheKeysOfTheCredentialsByDefault(): void
    {
        // @mago-expect lint:no-literal-password The secret is an environment placeholder, set by the test kernel.
        $harness = Harness::boot(['credentials' => ['tenant-2' => ['client_id' => 'client-2', 'client_secret' => '%env(URSSAF_TEST_SECRET)%']]]);

        static::assertSame(
            ['tenant-1', 'tenant-2'],
            $harness->kernel->getContainer()->getParameter(UrssafAttestationsVerificationCodeSecuriteBundle::PARAMETER_TENANTS),
        );
    }

    public function testNumericTenantIdsAreStrings(): void
    {
        // @mago-expect lint:no-literal-password The secret is an environment placeholder, set by the test kernel.
        $harness = Harness::boot(['credentials' => ['123' => ['client_id' => 'client-3', 'client_secret' => '%env(URSSAF_TEST_SECRET)%']]]);

        static::assertSame(
            ['tenant-1', '123'],
            $harness->kernel->getContainer()->getParameter(UrssafAttestationsVerificationCodeSecuriteBundle::PARAMETER_TENANTS),
        );
    }

    public function testTheTenantsCanBeListedExplicitly(): void
    {
        $harness = Harness::boot(['tenants' => ['tenant-9']]);

        static::assertSame(
            ['tenant-9'],
            $harness->kernel->getContainer()->getParameter(UrssafAttestationsVerificationCodeSecuriteBundle::PARAMETER_TENANTS),
        );
    }

    public function testThereIsNoTenantByDefaultWithACredentialsProvider(): void
    {
        $harness = Harness::boot(['credentials' => null, 'credentials_provider' => 'test.credentials_provider']);

        static::assertSame(
            [],
            $harness->kernel->getContainer()->getParameter(UrssafAttestationsVerificationCodeSecuriteBundle::PARAMETER_TENANTS),
        );
    }
}
