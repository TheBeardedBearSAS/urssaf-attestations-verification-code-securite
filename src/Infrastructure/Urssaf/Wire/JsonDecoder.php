<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use JsonException;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;

/**
 * Decodes an HTTP body into a {@see Payload}.
 */
final class JsonDecoder
{
    /**
     * @throws UnexpectedUrssafResponse when the body is not valid JSON or not a JSON object / array
     */
    public static function decode(string $json): Payload
    {
        try {
            return Payload::fromDecoded(json_decode($json, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING));
        } catch (JsonException $exception) {
            throw UnexpectedUrssafResponse::causedBy('body is not valid JSON', $exception);
        }
    }
}
