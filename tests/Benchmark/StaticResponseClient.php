<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Benchmark;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * PSR-18 client that answers every call without any network: the token response on the OAuth2 endpoint, otherwise the
 * JSON body configured for the HTTP method. Unlike the mock client of the tests, it keeps no history, so a benchmark
 * can call it any number of times.
 */
final readonly class StaticResponseClient implements ClientInterface
{
    /**
     * @param array<string, string> $bodiesByMethod
     */
    public function __construct(
        private ResponseFactoryInterface&StreamFactoryInterface $factory,
        private array $bodiesByMethod,
        private string $tokenBody,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $body = str_contains($request->getUri()->getPath(), '/oauth/')
            ? $this->tokenBody
            : $this->bodiesByMethod[$request->getMethod()] ?? '{}';

        return $this->factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream($body));
    }
}
