<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Recipe;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Testing\SimulatedCertificateVerificationGateway;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;

/**
 * The Symfony Flex recipe stays in line with the bundle: recipe/ is the source of the recipe published on
 * symfony/recipes-contrib (docs/adr/0009).
 */
final class FlexRecipeTest extends TestCase
{
    private const RECIPE = __DIR__ . '/../../../recipe/thebeardedbear/urssaf-attestations-verification-code-securite/0.1';

    public function testTheManifestRegistersTheBundleAndCopiesTheConfiguration(): void
    {
        // @mago-expect analysis:mixed-assignment JSON is untyped: the assertions below narrow it.
        $manifest = json_decode((string) file_get_contents(self::RECIPE . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        static::assertIsArray($manifest);
        static::assertSame([UrssafAttestationsVerificationCodeSecuriteBundle::class => ['all']], $manifest['bundles']);
        static::assertSame(['config/' => '%CONFIG_DIR%/'], $manifest['copy-from-recipe']);
        static::assertIsArray($manifest['env']);
        static::assertSame(['URSSAF_ENV' => 'sandbox', 'URSSAF_CLIENT_ID' => '', 'URSSAF_CLIENT_SECRET' => ''], array_diff_key($manifest['env'], ['#1' => true]));
    }

    public function testTheCopiedConfigurationBootsTheBundle(): void
    {
        /** @var array{urssaf_attestations_verification_code_securite: array<string, mixed>, 'when@test': array{urssaf_attestations_verification_code_securite: array<string, mixed>}} $config */
        $config = self::recipeConfig();
        $main = $config['urssaf_attestations_verification_code_securite'];
        // @mago-expect lint:no-literal-password Fake credentials of a throw-away test configuration.
        foreach (['URSSAF_ENV' => 'sandbox', 'URSSAF_CLIENT_ID' => 'client', 'URSSAF_CLIENT_SECRET' => 'secret'] as $name => $value) {
            $_SERVER[$name] = $value;
            $_ENV[$name] = $value;
        }
        try {
            // Merged over the test defaults (HTTP client, in-memory persistence, a test tenant).
            $harness = Harness::boot($main);
            $command = $harness->command('urssaf:check');
            static::assertSame(0, $command->execute(['--skip-remote' => true]));
            static::assertMatchesRegularExpression('/^\| credentials +\| default +\| ok /m', $command->getDisplay());

            static::assertTrue(Harness::boot([...$main, ...$config['when@test']['urssaf_attestations_verification_code_securite']])->has(SimulatedCertificateVerificationGateway::class));
        } finally {
            unset($_SERVER['URSSAF_ENV'], $_ENV['URSSAF_ENV'], $_SERVER['URSSAF_CLIENT_ID'], $_ENV['URSSAF_CLIENT_ID'], $_SERVER['URSSAF_CLIENT_SECRET'], $_ENV['URSSAF_CLIENT_SECRET']);
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function recipeConfig(): array
    {
        // @mago-expect analysis:mixed-assignment YAML is untyped: the assertion below narrows it.
        $config = Yaml::parseFile(self::RECIPE . '/config/packages/urssaf_attestations_verification_code_securite.yaml');
        static::assertIsArray($config);

        return $config;
    }
}
