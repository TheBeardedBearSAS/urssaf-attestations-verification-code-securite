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

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\CredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvariantViolation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\ApiRequest;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http\JsonApiClient;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonEncoder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerificationResultMapper;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\VerifyManyRequestMapper;

/**
 * HTTP implementation of {@see CertificateVerificationGateway}. Build it with {@see UrssafGatewayFactory}.
 */
final readonly class UrssafHttpGateway implements CertificateVerificationGateway
{
    public function __construct(
        private CredentialsProvider $credentialsProvider,
        private JsonApiClient $client,
        private ApiConfiguration $configuration,
        private VerificationResultMapper $resultMapper = new VerificationResultMapper(),
        private VerifyManyRequestMapper $requestMapper = new VerifyManyRequestMapper(),
    ) {}

    public function verify(TenantId $tenantId, SecurityCode $securityCode): VerificationResult
    {
        $query = ['codeSecurite' => $securityCode->value()];
        $domain = $this->configuration->domain();
        if ($domain instanceof VerificationDomain) {
            $query['domaine'] = $domain->value;
        }

        $payload = $this->client->send(
            $this->credentialsProvider->forTenant($tenantId),
            ApiRequest::get(Operation::VerifyCertificate, UrssafEndpoints::VERIFY_PATH, $query),
        );

        $results = $this->resultMapper->map($payload);
        if (\count($results) !== 1) {
            throw UnexpectedUrssafResponse::invalidPayload(
                \sprintf('expected exactly one result, got %d', \count($results)),
            );
        }

        return $results[0];
    }

    public function verifyMany(TenantId $tenantId, SecurityCode ...$securityCodes): array
    {
        $count = \count($securityCodes);
        if ($count < 1 || $count > self::MAX_CODES_PER_CALL) {
            throw InvariantViolation::because(
                'security_codes.count_out_of_range',
                \sprintf('The number of security codes must be between 1 and %d.', self::MAX_CODES_PER_CALL),
                ['count' => $count, 'min' => 1, 'max' => self::MAX_CODES_PER_CALL],
            );
        }

        $payload = $this->client->send(
            $this->credentialsProvider->forTenant($tenantId),
            ApiRequest::post(
                Operation::VerifyCertificates,
                UrssafEndpoints::VERIFY_PATH,
                JsonEncoder::encode($this->requestMapper->toBody(...$securityCodes)),
            ),
        );

        $results = $this->resultMapper->map($payload);
        if (\count($results) !== $count) {
            throw UnexpectedUrssafResponse::invalidPayload(
                \sprintf('expected %d results, got %d', $count, \count($results)),
            );
        }

        return $results;
    }
}
