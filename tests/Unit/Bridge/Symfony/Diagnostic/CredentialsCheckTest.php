<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Bridge\Symfony\Diagnostic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckContext;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CheckStatus;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\CredentialsCheck;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;

#[CoversClass(CredentialsCheck::class)]
final class CredentialsCheckTest extends TestCase
{
    public function testEveryTenantIsCheckedInOrder(): void
    {
        $check = new CredentialsCheck(new InMemoryCredentialsProvider(['a' => Credentials::of('client-a', 'secret-a')]));

        $results = $check->run(CheckContext::withoutRemoteCalls([TenantId::fromString('a'), TenantId::fromString('b')]));

        static::assertEquals([
            new CheckResult('credentials', 'a', CheckStatus::Ok, 'Credentials found.'),
            new CheckResult('credentials', 'b', CheckStatus::Failure, 'No credentials found for identifier "b".'),
        ], $results);
    }
}
