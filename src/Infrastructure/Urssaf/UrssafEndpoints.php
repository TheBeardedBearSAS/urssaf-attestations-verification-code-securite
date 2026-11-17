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

/**
 * Paths of the URSSAF API (relative to {@see Environment::baseUri()}). See docs/api-reference.md.
 */
final class UrssafEndpoints
{
    public const TOKEN_PATH = '/api/oauth/v1/token';
    public const TOKEN_SCOPE = 'attn.codes_securite';
    public const API_BASE_PATH = '/attn/verification/v1';
    public const VERIFY_PATH = '/codes_securite/verifier';
}
