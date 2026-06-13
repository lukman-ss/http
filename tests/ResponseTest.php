<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use Lukman\Http\HeaderBag;
use Lukman\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor and defaults
    // -------------------------------------------------------------------------

    public function testConstructorDefaults(): void
    {
        $res = new Response();
        $this->assertSame('', $res->content());
        $this->assertSame(200, $res->status());
        $this->assertInstanceOf(HeaderBag::class, $res->headers());
        $this->assertSame([], $res->headers()->all());
    }

    public function testConstructorCustomValues(): void
    {
        $res = new Response('Hello World', 201, ['Content-Type' => 'text/plain']);
        $this->assertSame('Hello World', $res->content());
        $this->assertSame(201, $res->status());
        $this->assertSame('text/plain', $res->headers()->get('Content-Type'));
    }

    // -------------------------------------------------------------------------
    // Setters / Fluency
    // -------------------------------------------------------------------------

    public function testHeaderSetsValueAndIsFluent(): void
    {
        $res = new Response();
        $returned = $res->header('X-Custom', 'foo');

        $this->assertSame($res, $returned);
        $this->assertSame('foo', $res->headers()->get('x-custom'));

        $res->header('X-Custom', 'bar');
        $this->assertSame('bar', $res->headers()->get('x-custom'));
    }

    public function testWithStatusChangesStatusAndIsFluent(): void
    {
        $res = new Response();
        $returned = $res->withStatus(404);

        $this->assertSame($res, $returned);
        $this->assertSame(404, $res->status());
    }

    public function testSetContentChangesContentAndIsFluent(): void
    {
        $res = new Response('old content');
        $returned = $res->setContent('new content');

        $this->assertSame($res, $returned);
        $this->assertSame('new content', $res->content());
    }

    public function testMethodsCanBeChained(): void
    {
        $res = (new Response())
            ->withStatus(201)
            ->setContent('created')
            ->header('Content-Type', 'text/plain')
            ->header('X-Trace', ['one', 'two']);

        $this->assertSame(201, $res->status());
        $this->assertSame('created', $res->content());
        $this->assertSame('text/plain', $res->headers()->get('content-type'));
        $this->assertSame(['one', 'two'], $res->headers()->get('x-trace'));
    }

    // -------------------------------------------------------------------------
    // headerLines()
    // -------------------------------------------------------------------------

    public function testHeaderLinesReturnsPreparedHeaders(): void
    {
        $res = new Response('', 200, [
            'Content-Type' => 'text/plain',
            'X-Trace' => ['one', 'two'],
        ]);

        $this->assertSame([
            'content-type: text/plain',
            'x-trace: one',
            'x-trace: two',
        ], $res->headerLines());
    }

    // -------------------------------------------------------------------------
    // send()
    // -------------------------------------------------------------------------

    public function testSendEchoesContent(): void
    {
        $res = new Response('Output content', 200);

        ob_start();
        $res->send();
        $output = ob_get_clean();

        $this->assertSame('Output content', $output);
    }
}
