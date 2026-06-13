<?php

declare(strict_types=1);

namespace Lukman\Http;

class RedirectResponse extends Response
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        string $url,
        int $status = 302,
        array $headers = [],
    ) {
        parent::__construct('', $status, $headers);

        $this->header('Location', $url);
    }
}
