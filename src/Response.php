<?php

declare(strict_types=1);

namespace Lukman\Http;

class Response
{
    private HeaderBag $headers;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        private string $content = '',
        private int $status = 200,
        array $headers = [],
    ) {
        $this->headers = new HeaderBag($headers);
    }

    /**
     * Get the response content.
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Get the HTTP status code.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Get the HeaderBag instance.
     */
    public function headers(): HeaderBag
    {
        return $this->headers;
    }

    /**
     * Set a header value.
     *
     * @param string|list<string> $value
     */
    public function header(string $name, string|array $value): self
    {
        $this->headers->set($name, $value);
        return $this;
    }

    /**
     * Change the HTTP status code.
     */
    public function withStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set the response content.
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Return header lines prepared for sending.
     *
     * @return list<string>
     */
    public function headerLines(): array
    {
        $lines = [];

        foreach ($this->headers->all() as $name => $values) {
            $values = is_array($values) ? $values : [$values];
            foreach ($values as $value) {
                $lines[] = $name . ': ' . $value;
            }
        }

        return $lines;
    }

    /**
     * Send HTTP response code, headers, and output content.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headerLines() as $line) {
                header($line, false);
            }
        }

        echo $this->content;
    }

    /**
     * Create a new JsonResponse.
     *
     * @param array<mixed>                       $data
     * @param array<string, string|list<string>> $headers
     */
    public static function json(array $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Create a new RedirectResponse.
     *
     * @param array<string, string|list<string>> $headers
     */
    public static function redirect(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status, $headers);
    }
}
