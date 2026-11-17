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

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificatesHandler;

/**
 * Application layer: the use case handlers, the only public services.
 */
final readonly class ApplicationRegistrar
{
    /** Use case handlers exposed to the host application. */
    private const HANDLERS = [VerifyCertificateHandler::class, VerifyCertificatesHandler::class];

    public function __construct(private ServicesConfigurator $services) {}

    public function register(): void
    {
        foreach (self::HANDLERS as $handler) {
            $this->services->set($handler)->autowire()->public();
        }
    }
}
