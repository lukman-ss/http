<?php

declare(strict_types=1);

namespace Lukman\Http;

class Request
{
    private string $method;
    private HeaderBag $headerBag;

    /** @var array<string, UploadedFile> */
    private array $files = [];

    /**
     * @param array<string, mixed>               $query   Query string parameters ($_GET).
     * @param array<string, mixed>               $request Posted body parameters ($_POST).
     * @param array<string, string|list<string>> $headers HTTP headers.
     * @param mixed                              $body    Raw request body.
     * @param array<string, mixed>               $files   Uploaded files.
     */
    public function __construct(
        string $method,
        private readonly string $uri,
        private readonly array $query = [],
        private readonly array $request = [],
        array $headers = [],
        private readonly mixed $body = null,
        array $files = [],
    ) {
        $this->method    = strtoupper(trim($method));
        $this->headerBag = new HeaderBag($headers);
        $this->files     = $this->normalizeUploadedFiles($files);
    }

    /**
     * Return the HTTP method in uppercase (e.g. GET, POST, PUT).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Return the full URI, including query string if present.
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Return the URI path component, stripping any query string.
     */
    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * Return a single query parameter, or all query parameters when $key is null.
     *
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Return a single posted body parameter, or all parameters when $key is null.
     *
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->request;
        }

        return $this->request[$key] ?? $default;
    }

    /**
     * Return a header value via HeaderBag (case-insensitive), or $default when absent.
     */
    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headerBag->get($name, $default);
    }

    /**
     * Return the underlying HeaderBag instance.
     */
    public function headers(): HeaderBag
    {
        return $this->headerBag;
    }

    /**
     * Return the raw request body.
     */
    public function body(): mixed
    {
        return $this->body;
    }

    /**
     * Check whether the request uses a given HTTP method (case-insensitive).
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper(trim($method));
    }

    /**
     * Return decoded JSON body, or a specific JSON key when provided.
     *
     * @return mixed
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $json = $this->jsonData();

        if ($key === null) {
            return $json;
        }

        return $json[$key] ?? $default;
    }

    /**
     * Return POST input merged with JSON body input.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->request, $this->jsonData());
    }

    /**
     * Return only existing input keys.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    /**
     * Return all input except the given keys.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $all = $this->all();

        foreach ($keys as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * Return a single uploaded file by key, all uploaded files when $key is null,
     * or $default when the key is absent.
     *
     * @return mixed
     */
    public function file(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? $default;
    }

    /**
     * Create a new Request instance from PHP globals or explicit source arrays.
     *
     * @param array<string, mixed>|null $server
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $request
     * @param array<string, mixed>|null $files
     */
    public static function capture(
        ?array $server = null,
        ?array $query = null,
        ?array $request = null,
        ?array $files = null,
        ?string $body = null,
    ): self
    {
        $useGlobalServer = $server === null;
        $server ??= $_SERVER;
        $query ??= $_GET;
        $request ??= $_POST;
        $files ??= $_FILES;

        $method = isset($server['REQUEST_METHOD']) && is_string($server['REQUEST_METHOD'])
            ? $server['REQUEST_METHOD']
            : 'GET';
        $uri = isset($server['REQUEST_URI']) && is_string($server['REQUEST_URI'])
            ? $server['REQUEST_URI']
            : '/';

        $headers = [];
        if ($useGlobalServer && function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            if (is_array($allHeaders)) {
                $headers = $allHeaders;
            }
        }

        if (empty($headers)) {
            $headers = self::extractHeadersFromServer($server);
        }

        if ($body === null) {
            $body = file_get_contents('php://input');
            if ($body === false) {
                $body = '';
            }
        }

        $uploadedFiles = self::normalizeFiles($files);

        return new self($method, $uri, $query, $request, $headers, $body, $uploadedFiles);
    }

    /**
     * Extract HTTP headers from the $_SERVER array.
     *
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    public static function extractHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_scalar($value) && !$value instanceof \Stringable) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = str_replace('_', '-', $key);
                $headers[$name] = (string) $value;
            }
        }
        return $headers;
    }

    /**
     * Convert the $_FILES superglobal into an array of UploadedFile instances.
     *
     * @param  array<string, mixed>       $files
     * @return array<string, UploadedFile>
     */
    public static function normalizeFiles(array $files): array
    {
        $result = [];
        foreach ($files as $key => $file) {
            if (
                isset($file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size'])
                && is_string($file['name'])
                && is_string($file['type'])
                && is_string($file['tmp_name'])
                && is_int($file['error'])
                && is_int($file['size'])
            ) {
                $result[$key] = new UploadedFile(
                    $file['name'],
                    $file['type'],
                    $file['tmp_name'],
                    $file['error'],
                    $file['size'],
                );
            }
        }
        return $result;
    }

    /**
     * @param array<string, UploadedFile> $files
     * @return array<string, UploadedFile>
     */
    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile) {
                $normalized[$key] = $file;
                continue;
            }

            $raw = self::normalizeFiles([$key => $file]);
            if (isset($raw[$key])) {
                $normalized[$key] = $raw[$key];
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonData(): array
    {
        $contentType = $this->header('Content-Type', '');
        if (is_array($contentType)) {
            $contentType = implode(', ', $contentType);
        }

        if (!str_contains(strtolower((string) $contentType), 'application/json')) {
            return [];
        }

        if (!is_string($this->body)) {
            return [];
        }

        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
