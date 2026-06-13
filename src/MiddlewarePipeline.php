<?php

declare(strict_types=1);

namespace Lukman\Http;

class MiddlewarePipeline implements RequestHandlerInterface
{
    /** @var list<MiddlewareInterface> */
    private array $middleware;

    /**
     * @param list<MiddlewareInterface> $middleware  Ordered list of middleware to execute.
     * @param RequestHandlerInterface  $handler     Final handler called when the stack is exhausted.
     */
    public function __construct(
        array $middleware,
        private readonly RequestHandlerInterface $handler,
    ) {
        $this->middleware = array_values($middleware);
    }

    /**
     * Execute the next middleware in the stack, falling through to the final handler
     * when no middleware remains.
     */
    public function handle(Request $request): Response
    {
        if ($this->middleware === []) {
            return $this->handler->handle($request);
        }

        // Pop the first middleware and build a new pipeline with the remainder.
        $current    = $this->middleware[0];
        $remaining  = array_slice($this->middleware, 1);

        $next = new self($remaining, $this->handler);

        return $current->process($request, $next);
    }
}
