<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\BundleConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection\ServiceRegistrar;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Diagnostic\InstallationCheck;

/**
 * Symfony integration of the URSSAF "API Attestations Vérification Code Sécurité" client (Symfony 6.4 and 7.x).
 *
 * @phpstan-import-type Config from ServiceRegistrar
 */
final class UrssafAttestationsVerificationCodeSecuriteBundle extends AbstractBundle
{
    public const ALIAS = 'urssaf_attestations_verification_code_securite';
    public const PARAMETER_TENANTS = 'urssaf_attestations_verification_code_securite.tenants';

    protected string $extensionAlias = self::ALIAS;

    /**
     * The bundle directory ({@see AbstractBundle} assumes a class one level below it), so that the framework finds the
     * shipped translations in Resources/translations.
     */
    public function getPath(): string
    {
        return __DIR__;
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        BundleConfiguration::define($definition);
    }

    /**
     * @param array<array-key, mixed> $config the configuration validated by {@see BundleConfiguration}
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        /** @var Config $validated */
        $validated = $config;
        $tenants = $validated['tenants'] !== [] ? $validated['tenants'] : array_map(strval(...), array_keys($validated['credentials']));
        $configurator->parameters()->set(self::PARAMETER_TENANTS, $tenants);
        (new ServiceRegistrar($configurator->services()))->register($validated);
        $container->registerForAutoconfiguration(InstallationCheck::class)->addTag(InstallationCheck::TAG);
    }
}
