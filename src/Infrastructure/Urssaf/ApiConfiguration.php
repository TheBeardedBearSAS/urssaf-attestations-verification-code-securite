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

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Exception\InvalidValue;

/**
 * Immutable connection settings of the URSSAF gateway.
 */
final readonly class ApiConfiguration
{
    public const DEFAULT_TOKEN_SAFETY_MARGIN_SECONDS = 60;
    public const DEFAULT_USER_AGENT = 'thebeardedbear-urssaf-attestations-verification-code-securite';

    /**
     * @param string|null             $baseUriOverride          replaces the environment host (e.g. a sandbox host or a proxy)
     * @param VerificationDomain|null $domain                   `domaine` query parameter of the GET operation (omitted when null)
     * @param int                     $tokenSafetyMarginSeconds a cached token is considered expired this many seconds early
     *
     * @throws InvalidValue "token_safety_margin.negative"
     */
    public function __construct(
        private Environment $environment,
        private ?string $baseUriOverride,
        private ?VerificationDomain $domain = VerificationDomain::Urssaf,
        private int $tokenSafetyMarginSeconds = self::DEFAULT_TOKEN_SAFETY_MARGIN_SECONDS,
        private string $userAgent = self::DEFAULT_USER_AGENT,
    ) {
        if ($tokenSafetyMarginSeconds < 0) {
            throw InvalidValue::because(
                'token_safety_margin.negative',
                'Invalid token_safety_margin: the value must not be negative.',
            );
        }
    }

    public static function forEnvironment(Environment $environment): self
    {
        return new self($environment, null);
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    public function baseUri(): string
    {
        return rtrim($this->baseUriOverride ?? $this->environment->baseUri(), '/');
    }

    public function tokenUri(): string
    {
        return $this->baseUri() . UrssafEndpoints::TOKEN_PATH;
    }

    /**
     * @param string $path one of the {@see UrssafEndpoints} paths relative to {@see UrssafEndpoints::API_BASE_PATH}
     */
    public function apiUri(string $path): string
    {
        return $this->baseUri() . UrssafEndpoints::API_BASE_PATH . $path;
    }

    public function domain(): ?VerificationDomain
    {
        return $this->domain;
    }

    public function tokenSafetyMarginSeconds(): int
    {
        return $this->tokenSafetyMarginSeconds;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }
}
