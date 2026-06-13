<?php

declare(strict_types=1);

namespace Lukman\Http;

class JsonResponse extends Response
{
    /**
     * @param array<mixed>                       $data
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        array $data = [],
        int $status = 200,
        array $headers = [],
    ) {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        parent::__construct($encoded, $status, $headers);

        $this->header('Content-Type', 'application/json');
    }
}
