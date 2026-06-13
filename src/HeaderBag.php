<?php

declare(strict_types=1);

namespace Lukman\Http;

use InvalidArgumentException;

class HeaderBag
{
    /** @var array<string, string|list<string>> */
    private array $headers = [];

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Set a header, overwriting any existing value(s).
     *
     * @param string|list<string> $value
     */
    public function set(string $name, string|array $value): void
    {
        $this->headers[$this->normalize($name)] = $this->normalizeValue($value);
    }

    /**
     * Add a value to a header without removing existing values.
     * If the header does not yet exist, it is created with the given string value.
     * If it already holds a string, it is converted to an array first.
     */
    public function add(string $name, string $value): void
    {
        $key = $this->normalize($name);

        if (!isset($this->headers[$key])) {
            $this->headers[$key] = $value;
            return;
        }

        $existing = $this->headers[$key];

        if (is_string($existing)) {
            $this->headers[$key] = [$existing, $value];
        } else {
            $this->headers[$key][] = $value;
        }
    }

    /**
     * Get the value of a header, or $default if it does not exist.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $key = $this->normalize($name);

        return $this->headers[$key] ?? $default;
    }

    /**
     * Return all headers as a normalized-lowercase-keyed array.
     *
     * @return array<string, string|list<string>>
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Check whether a header exists.
     */
    public function has(string $name): bool
    {
        return isset($this->headers[$this->normalize($name)]);
    }

    /**
     * Remove a header.
     */
    public function remove(string $name): void
    {
        unset($this->headers[$this->normalize($name)]);
    }

    /**
     * Replace all existing headers with the given set.
     *
     * @param array<string, string|list<string>> $headers
     */
    public function replace(array $headers): void
    {
        $this->headers = [];

        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function normalize(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * @param string|array<int, string> $value
     * @return string|list<string>
     */
    private function normalizeValue(string|array $value): string|array
    {
        if (is_string($value)) {
            return $value;
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException('Header values must be strings.');
            }
        }

        return array_values($value);
    }
}
