<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command\VerifyCertificateCommand;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle;

/**
 * Driving adapters: the console commands.
 */
final readonly class EntryPointRegistrar
{
    public function __construct(private ServicesConfigurator $services) {}

    public function registerConsole(): void
    {
        $this->services->set(VerifyCertificateCommand::class)
            ->autowire()
            ->arg('$configuredTenants', '%' . UrssafAttestationsVerificationCodeSecuriteBundle::PARAMETER_TENANTS . '%')
            ->tag('console.command');
    }
}
