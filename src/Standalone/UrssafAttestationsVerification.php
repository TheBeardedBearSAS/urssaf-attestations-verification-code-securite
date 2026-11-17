<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\CertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificatesHandler;

/**
 * Every use case of the package wired together, for applications without the Symfony bundle; built by
 * {@see UrssafAttestationsVerificationBuilder}. Each method returns a new handler (handlers are stateless).
 */
final readonly class UrssafAttestationsVerification
{
    public function __construct(private CertificateVerificationGateway $gateway) {}

    public function gateway(): CertificateVerificationGateway
    {
        return $this->gateway;
    }

    public function verifyCertificate(): VerifyCertificateHandler
    {
        return new VerifyCertificateHandler($this->gateway);
    }

    public function verifyCertificates(): VerifyCertificatesHandler
    {
        return new VerifyCertificatesHandler($this->gateway);
    }
}
