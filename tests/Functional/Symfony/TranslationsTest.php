<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support\Harness;

final class TranslationsTest extends TestCase
{
    /** Every violation code the package can raise (domain, application, infrastructure), in the order of the files. */
    private const CODES = [
        'access_token.empty',
        'access_token.negative_margin',
        'calendar_date.invalid_date',
        'calendar_date.invalid_format',
        'credentials.client_id_empty',
        'credentials.client_secret_empty',
        'credentials.not_found',
        'instant.invalid_date',
        'instant.invalid_format',
        'security_code.empty',
        'security_codes.count_out_of_range',
        'tenant_id.empty',
        'tenant_id.too_long',
        'token_safety_margin.negative',
        'violation.invalid_code',
        'violation.invalid_path',
    ];

    /** Locales shipped by the bundle. */
    private const LOCALES = ['en', 'fr'];

    /**
     * @return iterable<string, array{string}>
     */
    public static function locales(): iterable
    {
        foreach (self::LOCALES as $locale) {
            yield $locale => [$locale];
        }
    }

    #[DataProvider('locales')]
    public function testEveryViolationCodeHasAMessage(string $locale): void
    {
        static::assertSame(self::CODES, array_keys(self::messages($locale)));
    }

    public function testEveryCodeFoundInTheSourcesIsKnown(): void
    {
        $found = self::codesInSources();

        static::assertNotSame([], $found);
        static::assertSame([], array_values(array_diff($found, self::CODES)), 'Violation codes missing from the translations.');
    }

    #[DataProvider('locales')]
    public function testEveryMessageIsAFilledSentence(string $locale): void
    {
        foreach (self::messages($locale) as $code => $message) {
            static::assertMatchesRegularExpression('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $code);
            static::assertMatchesRegularExpression('/^\p{Lu}.+\.$/u', $message, $code);
        }
    }

    #[DataProvider('locales')]
    public function testEveryLocaleHasTheSamePlaceholdersAsTheEnglishFile(string $locale): void
    {
        $english = self::messages('en');
        foreach (self::messages($locale) as $code => $message) {
            static::assertSame(self::placeholders($english[$code]), self::placeholders($message), $code);
        }
    }

    public function testTheBundleTranslationsAreRegisteredInTheFramework(): void
    {
        $translator = Harness::boot()->privateService('translator', TranslatorInterface::class);
        $domain = UrssafAttestationsVerificationCodeSecuriteBundle::ALIAS;

        static::assertSame(
            'Le code de sécurité est obligatoire.',
            $translator->trans('security_code.empty', [], $domain, 'fr'),
        );
        static::assertSame(
            'The tenant id must not exceed 64 characters.',
            $translator->trans('tenant_id.too_long', ['%max_length%' => 64], $domain, 'en'),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function messages(string $locale): array
    {
        // @mago-expect analysis:mixed-assignment YAML is untyped; every entry is checked below.
        $parsed = Yaml::parseFile(\dirname(__DIR__, 3) . '/src/Bridge/Symfony/Resources/translations/' . UrssafAttestationsVerificationCodeSecuriteBundle::ALIAS . '.' . $locale . '.yaml');
        static::assertIsArray($parsed);
        $messages = [];
        foreach ($parsed as $code => $message) { // @mago-expect analysis:mixed-assignment narrowed by the assertion below.
            static::assertIsString($message);
            $messages[(string) $code] = $message;
        }

        return $messages;
    }

    /**
     * @return list<string>
     */
    private static function placeholders(string $message): array
    {
        $matches = [];
        preg_match_all('/%[a-z_]+%/', $message, $matches);

        return array_values(array_map(strval(...), $matches[0]));
    }

    /**
     * Dotted snake_case literals of the domain and application layers (violation codes given as literals).
     *
     * @return list<string>
     */
    private static function codesInSources(): array
    {
        $codes = [];
        foreach (['Domain', 'Application'] as $layer) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(\dirname(__DIR__, 3) . '/src/' . $layer, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                Assert::assertInstanceOf(SplFileInfo::class, $file);
                $matches = [];
                preg_match_all("/'([a-z][a-z0-9_]*(?:\\.[a-z][a-z0-9_]*)+)'/", (string) file_get_contents($file->getPathname()), $matches);
                array_push($codes, ...$matches[1]);
            }
        }

        return array_values(array_unique($codes));
    }
}
