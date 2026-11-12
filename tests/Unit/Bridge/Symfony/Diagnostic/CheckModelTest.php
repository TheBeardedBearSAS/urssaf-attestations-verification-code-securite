<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Bridge\Symfony\Diagnostic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckContext;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckStatus;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\StubCheck;

#[CoversClass(CheckContext::class)]
#[CoversClass(CheckResult::class)]
#[CoversClass(CheckStatus::class)]
final class CheckModelTest extends TestCase
{
    public function testAContextThatAllowsRemoteCalls(): void
    {
        $tenant = TenantId::fromString('tenant-1');

        $context = CheckContext::withRemoteCalls([$tenant]);

        static::assertSame([$tenant], $context->tenants);
        static::assertTrue($context->remoteCallsAllowed);
    }

    public function testAContextThatForbidsRemoteCalls(): void
    {
        $tenant = TenantId::fromString('tenant-1');

        $context = CheckContext::withoutRemoteCalls([$tenant]);

        static::assertSame([$tenant], $context->tenants);
        static::assertFalse($context->remoteCallsAllowed);
    }

    public function testTheStatusValues(): void
    {
        static::assertSame(
            ['ok', 'warning', 'failure', 'skipped'],
            array_map(static fn(CheckStatus $status): string => $status->value, CheckStatus::cases()),
        );
    }

    public function testACheckReturnsResults(): void
    {
        $check = new StubCheck();

        $results = $check->run(CheckContext::withRemoteCalls([TenantId::fromString('tenant-1')]));

        static::assertCount(1, $results);
        static::assertSame('stub', $results[0]->check);
        static::assertSame('', $results[0]->subject);
        static::assertSame(CheckStatus::Ok, $results[0]->status);
        static::assertSame('fine', $results[0]->detail);
    }
}
