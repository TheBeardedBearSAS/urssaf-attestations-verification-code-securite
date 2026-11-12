<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Functional\Symfony\Support;

use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Tests\Unit\Infrastructure\Urssaf\Support\Fixtures;

/**
 * Scripted URSSAF platform behind the mocked PSR-18 client. Every route answers the official fixtures by default and
 * can be switched to a given status or to a transport failure. Requests are recorded per route.
 */
final class HttpScript
{
    public const ROUTE_AUTHENTICATION = 'authentication';
    public const ROUTE_VERIFY = 'verify';
    public const ROUTE_VERIFY_MANY = 'verify_many';

    /** @var array<string, RouteEndpoint> */
    private array $routes;

    public function __construct(private readonly Client $client)
    {
        $this->routes = [
            self::ROUTE_AUTHENTICATION => new RouteEndpoint(200, Fixtures::load('token_response')),
            self::ROUTE_VERIFY => new RouteEndpoint(200, Fixtures::load('verify_response_valid')),
            self::ROUTE_VERIFY_MANY => new RouteEndpoint(200, Fixtures::load('verify_response_expired')),
        ];
        $matchers = [
            self::ROUTE_AUTHENTICATION => new RequestMatcher('/oauth/v1/token$', null, ['POST']),
            self::ROUTE_VERIFY => new RequestMatcher('/codes_securite/verifier$', null, ['GET']),
            self::ROUTE_VERIFY_MANY => new RequestMatcher('/codes_securite/verifier$', null, ['POST']),
        ];
        foreach ($matchers as $route => $matcher) {
            $this->client->on($matcher, $this->routes[$route]);
        }
    }

    public function calls(string $route): int
    {
        return $this->routes[$route]->calls();
    }

    /**
     * @param array<string, string> $headers
     */
    public function respond(string $route, int $status, string $body, array $headers = []): void
    {
        $this->routes[$route]->respond($status, $body, $headers);
    }

    public function failTransport(string $route): void
    {
        $this->routes[$route]->failTransport();
    }
}
