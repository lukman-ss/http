<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use Lukman\Http\MiddlewareInterface;
use Lukman\Http\MiddlewarePipeline;
use Lukman\Http\Request;
use Lukman\Http\RequestHandlerInterface;
use Lukman\Http\Response;
use PHPUnit\Framework\TestCase;

class MiddlewarePipelineTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Test doubles
    // -------------------------------------------------------------------------

    /** Final handler that always returns a fixed 200 Response. */
    private function makeHandler(string $content = 'final', int $status = 200): RequestHandlerInterface
    {
        return new class ($content, $status) implements RequestHandlerInterface {
            public function __construct(
                private readonly string $content,
                private readonly int $status,
            ) {}

            public function handle(Request $request): Response
            {
                return new Response($this->content, $this->status);
            }
        };
    }

    /**
     * Middleware that appends a marker to an external ArrayObject log and then calls next.
     */
    private function makePassthroughMiddleware(\ArrayObject $log, string $marker): MiddlewareInterface
    {
        return new class ($log, $marker) implements MiddlewareInterface {
            public function __construct(
                private readonly \ArrayObject $log,
                private readonly string $marker,
            ) {}

            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                $this->log->append($this->marker);
                return $handler->handle($request);
            }
        };
    }

    /**
     * Middleware that short-circuits by returning its own Response without calling next.
     */
    private function makeShortCircuitMiddleware(string $content, int $status = 401): MiddlewareInterface
    {
        return new class ($content, $status) implements MiddlewareInterface {
            public function __construct(
                private readonly string $content,
                private readonly int $status,
            ) {}

            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                return new Response($this->content, $this->status);
            }
        };
    }

    private function makeRequest(): Request
    {
        return new Request('GET', '/');
    }

    // -------------------------------------------------------------------------
    // No middleware — final handler is called directly
    // -------------------------------------------------------------------------

    public function testFinalHandlerCalledWhenNoMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline([], $this->makeHandler('ok'));
        $response = $pipeline->handle($this->makeRequest());

        $this->assertSame('ok', $response->content());
        $this->assertSame(200, $response->status());
    }

    // -------------------------------------------------------------------------
    // Ordered execution
    // -------------------------------------------------------------------------

    public function testSinglePassthroughMiddlewareCallsFinalHandler(): void
    {
        $log = new \ArrayObject();
        $pipeline = new MiddlewarePipeline(
            [$this->makePassthroughMiddleware($log, 'A')],
            $this->makeHandler('done'),
        );
        $response = $pipeline->handle($this->makeRequest());

        $this->assertSame(['A'], $log->getArrayCopy());
        $this->assertSame('done', $response->content());
    }

    public function testMultipleMiddlewareAreExecutedInOrder(): void
    {
        $log = new \ArrayObject();
        $pipeline = new MiddlewarePipeline(
            [
                $this->makePassthroughMiddleware($log, 'first'),
                $this->makePassthroughMiddleware($log, 'second'),
                $this->makePassthroughMiddleware($log, 'third'),
            ],
            $this->makeHandler('final'),
        );
        $pipeline->handle($this->makeRequest());

        $this->assertSame(['first', 'second', 'third'], $log->getArrayCopy());
    }

    public function testMiddlewareExecutionOrderIncludesResponseUnwind(): void
    {
        $log = new \ArrayObject();

        $first = new class ($log) implements MiddlewareInterface {
            public function __construct(private readonly \ArrayObject $log) {}

            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                $this->log->append('first-before');
                $response = $handler->handle($request);
                $this->log->append('first-after');

                return $response;
            }
        };

        $second = new class ($log) implements MiddlewareInterface {
            public function __construct(private readonly \ArrayObject $log) {}

            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                $this->log->append('second-before');
                $response = $handler->handle($request);
                $this->log->append('second-after');

                return $response;
            }
        };

        $handler = new class ($log) implements RequestHandlerInterface {
            public function __construct(private readonly \ArrayObject $log) {}

            public function handle(Request $request): Response
            {
                $this->log->append('final');

                return new Response('ok');
            }
        };

        $pipeline = new MiddlewarePipeline([$first, $second], $handler);
        $pipeline->handle($this->makeRequest());

        $this->assertSame([
            'first-before',
            'second-before',
            'final',
            'second-after',
            'first-after',
        ], $log->getArrayCopy());
    }

    public function testFinalHandlerCalledAfterAllMiddleware(): void
    {
        $log = new \ArrayObject();
        $pipeline = new MiddlewarePipeline(
            [
                $this->makePassthroughMiddleware($log, 'A'),
                $this->makePassthroughMiddleware($log, 'B'),
            ],
            $this->makeHandler('result', 201),
        );
        $response = $pipeline->handle($this->makeRequest());

        $this->assertSame(201, $response->status());
        $this->assertSame('result', $response->content());
        $this->assertCount(2, $log);
    }

    // -------------------------------------------------------------------------
    // Short-circuit
    // -------------------------------------------------------------------------

    public function testShortCircuitMiddlewarePreventsNextMiddleware(): void
    {
        $log = new \ArrayObject();
        $pipeline = new MiddlewarePipeline(
            [
                $this->makePassthroughMiddleware($log, 'before'),
                $this->makeShortCircuitMiddleware('unauthorized', 401),
                $this->makePassthroughMiddleware($log, 'after'),  // should NOT run
            ],
            $this->makeHandler('final'),
        );
        $response = $pipeline->handle($this->makeRequest());

        $this->assertSame(401, $response->status());
        $this->assertSame('unauthorized', $response->content());
        $this->assertSame(['before'], $log->getArrayCopy());        // 'after' was never reached
    }

    public function testFirstMiddlewareShortCircuitsEntirePipeline(): void
    {
        $log = new \ArrayObject();
        $finalCalls = new \ArrayObject();
        $handler = new class ($finalCalls) implements RequestHandlerInterface {
            public function __construct(private readonly \ArrayObject $finalCalls) {}

            public function handle(Request $request): Response
            {
                $this->finalCalls->append('called');

                return new Response('final');
            }
        };

        $pipeline = new MiddlewarePipeline(
            [
                $this->makeShortCircuitMiddleware('blocked', 403),
                $this->makePassthroughMiddleware($log, 'skipped'),
            ],
            $handler,
        );
        $response = $pipeline->handle($this->makeRequest());

        $this->assertSame(403, $response->status());
        $this->assertSame('blocked', $response->content());
        $this->assertSame([], $log->getArrayCopy());    // nothing after the short-circuit ran
        $this->assertSame([], $finalCalls->getArrayCopy());
    }

    // -------------------------------------------------------------------------
    // Response integrity
    // -------------------------------------------------------------------------

    public function testResponseContentIsPreservedThroughPipeline(): void
    {
        $pipeline = new MiddlewarePipeline(
            [],
            $this->makeHandler('Hello World', 200),
        );
        $response = $pipeline->handle($this->makeRequest());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Hello World', $response->content());
    }

    public function testMiddlewareCanDecorateResponse(): void
    {
        // Middleware that adds a header to the response returned by next.
        $decorator = new class implements MiddlewareInterface {
            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                $response = $handler->handle($request);
                return $response->header('X-Decorated', 'yes');
            }
        };

        $pipeline = new MiddlewarePipeline(
            [$decorator],
            $this->makeHandler('body'),
        );
        $response = $pipeline->handle($this->makeRequest());

        $this->assertSame('body', $response->content());
        $this->assertSame('yes', $response->headers()->get('x-decorated'));
    }

    // -------------------------------------------------------------------------
    // Pipeline implements RequestHandlerInterface
    // -------------------------------------------------------------------------

    public function testPipelineImplementsRequestHandlerInterface(): void
    {
        $pipeline = new MiddlewarePipeline([], $this->makeHandler());
        $this->assertInstanceOf(RequestHandlerInterface::class, $pipeline);
    }
}
