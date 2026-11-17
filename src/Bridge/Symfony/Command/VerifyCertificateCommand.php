<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\Command;

use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificateHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificates;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificatesHandler;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;

#[AsCommand(name: 'urssaf:certificate:verify', description: 'Verify URSSAF certificates by security code')]
final class VerifyCertificateCommand extends Command
{
    /**
     * @param list<string> $configuredTenants
     */
    public function __construct(
        private readonly VerifyCertificateHandler $verifyOne,
        private readonly VerifyCertificatesHandler $verifyMany,
        private readonly array $configuredTenants,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('codes', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Security codes printed on the certificates');
        CommandOptions::addTenant($this, 'Tenant id (default: the only configured tenant)');
        CommandOptions::addFormat($this);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return CommandReports::guard($output, function () use ($input, $output): int {
            $format = CommandOptions::format($input);
            $tenant = CommandOptions::tenantOrSingleConfigured($input, $this->configuredTenants);
            /** @var list<string> $values */
            $values = $input->getArgument('codes');
            $codes = array_map(SecurityCode::fromString(...), $values);

            $results = \count($codes) === 1
                ? [($this->verifyOne)(new VerifyCertificate($tenant, $codes[0]))]
                : ($this->verifyMany)(new VerifyCertificates($tenant, ...$codes));

            return CommandReports::renderVerification($output, $results, $format);
        });
    }
}
