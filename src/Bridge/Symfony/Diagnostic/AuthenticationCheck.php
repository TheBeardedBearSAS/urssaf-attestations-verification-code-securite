<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic;

use Override;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\UrssafException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\OAuth2TokenProvider;

/**
 * URSSAF grants an access token to every tenant: any cached token is dropped first, so that it never hides refused
 * credentials. The token provider given here must use a token store of its own (the bundle gives it an in-memory one):
 * the tokens of the application are left untouched.
 */
final readonly class AuthenticationCheck implements InstallationCheck
{
    private const NAME = 'authentication';

    public function __construct(private CredentialsProvider $credentials, private OAuth2TokenProvider $tokens) {}

    #[Override]
    public function run(CheckContext $context): array
    {
        if (!$context->remoteCallsAllowed) {
            return [new CheckResult(self::NAME, '', CheckStatus::Skipped, 'Not checked (--skip-remote).')];
        }

        return array_map($this->check(...), $context->tenants);
    }

    private function check(TenantId $tenantId): CheckResult
    {
        try {
            $credentials = $this->credentials->forTenant($tenantId);
            $this->tokens->invalidate($credentials);
            $this->tokens->accessToken($credentials, Operation::VerifyCertificate);
        } catch (UrssafException $failure) {
            return new CheckResult(self::NAME, $tenantId->value(), CheckStatus::Failure, $failure->getMessage());
        }

        return new CheckResult(self::NAME, $tenantId->value(), CheckStatus::Ok, 'Access token granted by URSSAF.');
    }
}
