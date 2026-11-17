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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerificationResult;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\UrssafException;

/**
 * Execution guard and output of the console commands. The output never contains personal data (holder, NIR, names).
 */
final class CommandReports
{
    /**
     * Runs a command body, turning package failures and invalid options into an error message and a failure status.
     *
     * @param callable(): int $action
     * @param-immediately-invoked-callable $action
     */
    public static function guard(OutputInterface $output, callable $action): int
    {
        try {
            return $action();
        } catch (UrssafException|InvalidOptionException $failure) {
            $output->writeln(\sprintf('<error>%s</error>', $failure->getMessage()));

            return Command::FAILURE;
        }
    }

    /**
     * Prints the verification results as a table or as JSON; fails when at least one code is rejected.
     *
     * @param list<VerificationResult> $results
     */
    public static function renderVerification(OutputInterface $output, array $results, string $format): int
    {
        $rows = array_map(self::describe(...), $results);
        if ($format === CommandOptions::FORMAT_JSON) {
            $output->writeln(json_encode($rows, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), OutputInterface::OUTPUT_RAW);
        }
        if ($format === CommandOptions::FORMAT_TABLE) {
            self::renderTable($output, $rows);
        }

        foreach ($results as $result) {
            if (!$result->isValid()) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<array{code: string, valid: bool, nature: ?string, issuing_body: ?string, issued_on: ?string, fault_code: ?string, fault_name: ?string}> $rows
     */
    private static function renderTable(OutputInterface $output, array $rows): void
    {
        $table = new Table($output);
        $table->setHeaders(['Code', 'Result', 'Nature', 'Issuing body', 'Issued on', 'Fault']);
        foreach ($rows as $row) {
            $table->addRow([
                $row['code'],
                $row['valid'] ? 'valid' : 'rejected',
                $row['nature'] ?? '',
                $row['issuing_body'] ?? '',
                $row['issued_on'] ?? '',
                implode(' - ', array_filter([$row['fault_code'], $row['fault_name']], static fn(?string $part): bool => $part !== null)),
            ]);
        }
        $table->render();
    }

    /**
     * The non-personal fields of a result.
     *
     * @return array{code: string, valid: bool, nature: ?string, issuing_body: ?string, issued_on: ?string, fault_code: ?string, fault_name: ?string}
     */
    private static function describe(VerificationResult $result): array
    {
        $certificate = $result instanceof VerifiedCertificate ? $result : null;
        $rejected = $result instanceof RejectedCode ? $result : null;

        return [
            'code' => $result->securityCode()->value(),
            'valid' => $result->isValid(),
            'nature' => $certificate?->natureCode(),
            'issuing_body' => $certificate?->issuingBody()->label(),
            'issued_on' => $certificate?->issuedOn()?->toString(),
            'fault_code' => $rejected?->faultCode(),
            'fault_name' => $rejected?->faultName(),
        ];
    }
}
