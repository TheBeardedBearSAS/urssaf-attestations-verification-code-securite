<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SensitiveParameter;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Clock;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\IdGenerator;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Operation;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafAuthenticationFailed;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafRequestRejected;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Urssaf\UrssafUnavailable;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\ErrorPayloadParser;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\JsonDecoder;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire\Payload;

/**
 * Authenticated JSON transport over PSR-18. Build the gateway with
 * {@see \TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\UrssafGatewayFactory}.
 *
 * Per call: bearer token and `x-request-id`. A 401 invalidates the
 * cached token and the call is replayed exactly once. Statuses are then mapped to the gateway exceptions.
 * Neither the token nor request/response bodies are logged.
 *
 * @internal
 */
final readonly class JsonApiClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private OAuth2TokenProvider $tokenProvider,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private ApiConfiguration $configuration,
        private LoggerInterface $logger = new NullLogger(),
        private ErrorPayloadParser $errorParser = new ErrorPayloadParser(),
    ) {}

    /**
     * @throws UrssafRequestRejected      HTTP 400 (with the parsed error list, possibly empty)
     * @throws UrssafAuthenticationFailed HTTP 401 (after the retry), 403, 512
     * @throws UrssafUnavailable          HTTP 429/500/503 or transport error
     * @throws UnexpectedUrssafResponse   any other status or unreadable body
     */
    public function send(Credentials $credentials, ApiRequest $request): Payload
    {
        return $this->exchange($credentials, $request)->payload();
    }

    /**
     * Same as {@see self::send()}, also returning the request id of the (last) attempt.
     *
     * @throws UrssafRequestRejected
     * @throws UrssafAuthenticationFailed
     * @throws UrssafUnavailable
     * @throws UnexpectedUrssafResponse
     */
    public function exchange(Credentials $credentials, ApiRequest $request): ApiResponse
    {
        [$response, $requestId] = $this->attempt($credentials, $request);

        if ($response->getStatusCode() === 401) {
            $this->tokenProvider->invalidate($credentials);
            [$response, $requestId] = $this->attempt($credentials, $request);
        }

        return new ApiResponse($this->interpret($request->operation(), $response), $requestId);
    }

    /**
     * @return array{ResponseInterface, string} the response and the request id
     */
    private function attempt(Credentials $credentials, ApiRequest $request): array
    {
        $operation = $request->operation();
        $requestId = $this->idGenerator->generate();
        $httpRequest = $this->buildRequest($request, $this->tokenProvider->accessToken($credentials, $operation), $requestId);
        $context = ['method' => $request->method(), 'path' => $request->path(), 'request_id' => $requestId];
        $startedAt = $this->clock->now();

        try {
            $response = $this->httpClient->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $exception) {
            $this->logger->warning('URSSAF call failed: transport error.', $context + [
                'duration_ms' => Duration::millisecondsSince($startedAt, $this->clock->now()),
                'exception' => $exception::class,
            ]);

            throw UrssafUnavailable::dueToTransportError($operation, $exception);
        }

        $this->logger->info('URSSAF call.', $context + [
            'status' => $response->getStatusCode(),
            'duration_ms' => Duration::millisecondsSince($startedAt, $this->clock->now()),
        ]);

        return [$response, $requestId];
    }

    private function buildRequest(ApiRequest $request, #[SensitiveParameter] string $token, string $requestId): RequestInterface
    {
        $uri = $this->configuration->apiUri($request->path());
        if ($request->query() !== []) {
            $uri .= '?' . http_build_query($request->query(), '', '&', PHP_QUERY_RFC3986);
        }

        $httpRequest = $this->requestFactory
            ->createRequest($request->method(), $uri)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('x-request-id', $requestId)
            ->withHeader('User-Agent', $this->configuration->userAgent());

        $body = $request->jsonBody();
        if ($body !== null) {
            $httpRequest = $httpRequest->withBody($this->streamFactory->createStream($body));
        }

        return $httpRequest;
    }

    private function interpret(Operation $operation, ResponseInterface $response): Payload
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status >= 200 && $status < 300) {
            return JsonDecoder::decode($body);
        }

        if ($status === 400) {
            $errors = $this->errorParser->parseBody($body);

            throw UrssafRequestRejected::withErrors($operation, $status, $errors);
        }

        if (\in_array($status, [401, 403, 512], true)) {
            throw UrssafAuthenticationFailed::withStatus($status);
        }

        if (\in_array($status, [429, 500, 503], true)) {
            throw UrssafUnavailable::withStatus($operation, $status, RetryAfter::seconds($response));
        }

        throw UnexpectedUrssafResponse::unexpectedStatus($operation, $status);
    }
}
