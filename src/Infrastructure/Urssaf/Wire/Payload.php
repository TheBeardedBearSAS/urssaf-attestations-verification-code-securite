<?php

declare(strict_types=1);

namespace TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Wire;

use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Exception\UnexpectedUrssafResponse;

/**
 * Typed, defensive view over a decoded JSON document. This is the only place where untyped wire data is read:
 * every accessor either returns a well-typed value or throws {@see UnexpectedUrssafResponse}.
 */
final readonly class Payload
{
    /**
     * @param array<array-key, array<array-key, mixed>|scalar|null> $data
     */
    private function __construct(private array $data) {}

    /**
     * @throws UnexpectedUrssafResponse when the decoded JSON is neither an object nor an array
     */
    public static function fromDecoded(mixed $decoded): self
    {
        if (!\is_array($decoded)) {
            throw UnexpectedUrssafResponse::invalidPayload('body is neither a JSON object nor a JSON array');
        }

        return self::wrap($decoded);
    }

    /**
     * @param array<array-key, mixed> $data decoded JSON object or array
     */
    private static function wrap(array $data): self
    {
        /** @var array<array-key, array<array-key, mixed>|scalar|null> $typed */
        $typed = $data;

        return new self($typed);
    }

    public function isList(): bool
    {
        return array_is_list($this->data);
    }

    /**
     * Keys of this object, in document order.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(\strval(...), array_keys($this->data));
    }

    /**
     * String value of a key. Null when absent, null or of another type.
     */
    public function string(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @throws UnexpectedUrssafResponse when the key is absent or its value is not a non-empty string
     */
    public function requireString(string $key): string
    {
        $value = $this->string($key) ?? '';

        if ($value === '') {
            throw UnexpectedUrssafResponse::invalidPayload(\sprintf('field "%s" is missing or empty', $key));
        }

        return $value;
    }

    /**
     * Nested JSON object. Null when absent.
     *
     * @throws UnexpectedUrssafResponse when present but not an object
     */
    public function object(string $key): ?self
    {
        $value = $this->data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (!\is_array($value)) {
            throw UnexpectedUrssafResponse::invalidPayload(\sprintf('field "%s" is not an object', $key));
        }

        return self::wrap($value);
    }

    /**
     * Nested list of objects. Empty when absent.
     *
     * @return list<self>
     *
     * @throws UnexpectedUrssafResponse when present but not a list of objects
     */
    public function items(string $key): array
    {
        return $this->object($key)?->entries() ?? [];
    }

    /**
     * Elements of a top-level JSON array, each of which must be an object.
     *
     * @return list<self>
     *
     * @throws UnexpectedUrssafResponse when this payload is not a list of objects
     */
    public function entries(): array
    {
        if (!array_is_list($this->data)) {
            throw UnexpectedUrssafResponse::invalidPayload('expected a JSON array');
        }

        return array_map(
            static fn(array|bool|float|int|string|null $entry): self => \is_array($entry)
                ? self::wrap($entry)
                : throw UnexpectedUrssafResponse::invalidPayload('expected a JSON array of objects'),
            $this->data,
        );
    }
}
