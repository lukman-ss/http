<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use Lukman\Http\JsonResponse;
use Lukman\Http\RedirectResponse;
use Lukman\Http\Response;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor / content
    // -------------------------------------------------------------------------

    public function testDefaultsToEmptyArrayWithStatus200(): void
    {
        $res = new JsonResponse();
        $this->assertSame('[]', $res->content());
        $this->assertSame(200, $res->status());
    }

    public function testEncodesDataAsJson(): void
    {
        $res = new JsonResponse(['name' => 'lukman', 'active' => true]);
        $this->assertSame('{"name":"lukman","active":true}', $res->content());
    }

    public function testUnescapedUnicode(): void
    {
        $res = new JsonResponse(['msg' => 'Héllo wörld']);
        $this->assertStringContainsString('Héllo wörld', $res->content());
    }

    public function testUnescapedSlashes(): void
    {
        $res = new JsonResponse(['url' => 'https://example.com/path']);
        $this->assertStringContainsString('https://example.com/path', $res->content());
        $this->assertStringNotContainsString('https:\/\/', $res->content());
    }

    public function testNestedArrayIsEncoded(): void
    {
        $data = ['user' => ['id' => 1, 'roles' => ['admin', 'editor']]];
        $res = new JsonResponse($data);
        $this->assertSame(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $res->content());
    }

    // -------------------------------------------------------------------------
    // Content-Type header
    // -------------------------------------------------------------------------

    public function testSetsContentTypeHeader(): void
    {
        $res = new JsonResponse();
        $this->assertSame('application/json', $res->headers()->get('content-type'));
    }

    public function testContentTypeOverridesUserProvidedHeader(): void
    {
        $res = new JsonResponse([], 200, ['Content-Type' => 'text/plain']);
        $this->assertSame('application/json', $res->headers()->get('content-type'));
    }

    // -------------------------------------------------------------------------
    // Status code
    // -------------------------------------------------------------------------

    public function testCustomStatusCode(): void
    {
        $res = new JsonResponse(['error' => 'not found'], 404);
        $this->assertSame(404, $res->status());
    }

    // -------------------------------------------------------------------------
    // Extra headers
    // -------------------------------------------------------------------------

    public function testExtraHeadersArePreserved(): void
    {
        $res = new JsonResponse([], 200, ['X-Api-Version' => '2']);
        $this->assertSame('2', $res->headers()->get('x-api-version'));
    }

    // -------------------------------------------------------------------------
    // Inheritance
    // -------------------------------------------------------------------------

    public function testIsInstanceOfResponse(): void
    {
        $this->assertInstanceOf(Response::class, new JsonResponse());
    }

    // -------------------------------------------------------------------------
    // Response::json() factory
    // -------------------------------------------------------------------------

    public function testResponseJsonFactoryReturnsJsonResponse(): void
    {
        $res = Response::json(['ok' => true], 201);
        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertSame(201, $res->status());
        $this->assertStringContainsString('"ok":true', $res->content());
        $this->assertSame('application/json', $res->headers()->get('content-type'));
    }

    public function testResponseJsonFactoryPreservesCustomHeaders(): void
    {
        $res = Response::json(['ok' => true], 202, ['X-Trace' => 'abc']);

        $this->assertSame(202, $res->status());
        $this->assertSame('abc', $res->headers()->get('x-trace'));
        $this->assertSame('application/json', $res->headers()->get('content-type'));
    }

    // -------------------------------------------------------------------------
    // send()
    // -------------------------------------------------------------------------

    public function testSendEchoesJsonContent(): void
    {
        $res = new JsonResponse(['hello' => 'world']);

        ob_start();
        $res->send();
        $output = ob_get_clean();

        $this->assertSame('{"hello":"world"}', $output);
    }
}
