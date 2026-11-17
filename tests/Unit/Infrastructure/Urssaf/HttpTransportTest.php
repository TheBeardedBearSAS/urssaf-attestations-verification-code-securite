<?php

declare(strict_types=1);

/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;

#[CoversClass(HttpTransport::class)]
final class HttpTransportTest extends TestCase
{
    public function testItExposesItsCollaborators(): void
    {
        $client = new Client();
        $requests = new Psr17Factory();
        $streams = new Psr17Factory();

        $transport = new HttpTransport($client, $requests, $streams);

        static::assertSame($client, $transport->client());
        static::assertSame($requests, $transport->requestFactory());
        static::assertSame($streams, $transport->streamFactory());
    }
}
