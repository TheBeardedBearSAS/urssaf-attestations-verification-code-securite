<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CommandOptions;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\CommandReports;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\VerifyCertificateCommand;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\HttpScript;

#[CoversClass(VerifyCertificateCommand::class)]
#[CoversClass(CommandOptions::class)]
#[CoversClass(CommandReports::class)]
final class VerifyCertificateTenantTest extends TestCase
{
    private const COMMAND = 'urssaf:certificate:verify';
    private const REQUIRED = 'The --tenant option is required unless exactly one tenant is configured ("urssaf_attestations_verification_code_securite.tenants").';

    public function testTheOnlyConfiguredTenantIsUsedByDefault(): void
    {
        $harness = Harness::boot();
        $tester = $harness->command(self::COMMAND);

        static::assertSame(0, $tester->execute(['codes' => ['42TROLLQU3DT999']]));
        static::assertStringContainsString('client_id=client-1', self::lastAuthenticationBody($harness));
    }

    public function testTheTenantCanBeGivenExplicitly(): void
    {
        $harness = Harness::boot(self::twoTenants());
        $tester = $harness->command(self::COMMAND);

        static::assertSame(0, $tester->execute(['codes' => ['42TROLLQU3DT999'], '--tenant' => 'tenant-2']));
        static::assertStringContainsString('client_id=client-2', self::lastAuthenticationBody($harness));
    }

    public function testTheTenantIsRequiredWhenSeveralAreConfigured(): void
    {
        $harness = Harness::boot(self::twoTenants());
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999']]));
        static::assertSame(self::REQUIRED, trim($tester->getDisplay()));
        static::assertSame(0, $harness->urssaf->calls(HttpScript::ROUTE_AUTHENTICATION));
    }

    public function testTheTenantIsRequiredWhenNoneIsConfigured(): void
    {
        $harness = Harness::boot(['credentials' => null, 'credentials_provider' => 'test.credentials_provider']);
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999']]));
        static::assertSame(self::REQUIRED, trim($tester->getDisplay()));
        static::assertSame(0, $tester->execute(['codes' => ['42TROLLQU3DT999'], '--tenant' => 'tenant-1']));
    }

    public function testABlankTenantIsRejected(): void
    {
        $harness = Harness::boot();
        $tester = $harness->command(self::COMMAND);

        static::assertSame(1, $tester->execute(['codes' => ['42TROLLQU3DT999'], '--tenant' => ' ']));
        static::assertSame('Invalid tenant_id: the value must not be empty.', trim($tester->getDisplay()));
    }

    /**
     * @return array<string, mixed>
     */
    private static function twoTenants(): array
    {
        // @mago-expect lint:no-literal-password The secret is an environment placeholder, set by the test kernel.
        return ['credentials' => ['tenant-2' => ['client_id' => 'client-2', 'client_secret' => '%env(URSSAF_TEST_SECRET)%']]];
    }

    private static function lastAuthenticationBody(Harness $harness): string
    {
        foreach (array_reverse($harness->client->getRequests()) as $request) {
            if ($request->getMethod() === 'POST' && str_ends_with($request->getUri()->getPath(), '/oauth/v1/token')) {
                return (string) $request->getBody();
            }
        }

        static::fail('No authentication request.');
    }
}
