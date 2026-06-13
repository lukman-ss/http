<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use Lukman\Http\RedirectResponse;
use Lukman\Http\Response;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor / Location header
    // -------------------------------------------------------------------------

    public function testLocationHeaderIsSet(): void
    {
        $res = new RedirectResponse('https://example.com');
        $this->assertSame('https://example.com', $res->headers()->get('location'));
    }

    public function testContentIsEmptyString(): void
    {
        $res = new RedirectResponse('/home');
        $this->assertSame('', $res->content());
    }

    // -------------------------------------------------------------------------
    // Status code
    // -------------------------------------------------------------------------

    public function testDefaultStatusIs302(): void
    {
        $res = new RedirectResponse('/home');
        $this->assertSame(302, $res->status());
    }

    public function testCustomStatusCode301(): void
    {
        $res = new RedirectResponse('/new-path', 301);
        $this->assertSame(301, $res->status());
    }

    public function testCustomStatusCode307(): void
    {
        $res = new RedirectResponse('/temporary', 307);
        $this->assertSame(307, $res->status());
    }

    // -------------------------------------------------------------------------
    // Extra headers
    // -------------------------------------------------------------------------

    public function testExtraHeadersArePreserved(): void
    {
        $res = new RedirectResponse('/home', 302, ['X-Reason' => 'logged-out']);
        $this->assertSame('logged-out', $res->headers()->get('x-reason'));
        $this->assertSame('/home', $res->headers()->get('location'));
    }

    public function testLocationOverridesUserProvidedHeader(): void
    {
        $res = new RedirectResponse('/final', 302, ['Location' => '/ignored']);
        $this->assertSame('/final', $res->headers()->get('location'));
    }

    // -------------------------------------------------------------------------
    // Inheritance
    // -------------------------------------------------------------------------

    public function testIsInstanceOfResponse(): void
    {
        $this->assertInstanceOf(Response::class, new RedirectResponse('/'));
    }

    // -------------------------------------------------------------------------
    // Response::redirect() factory
    // -------------------------------------------------------------------------

    public function testResponseRedirectFactoryReturnsRedirectResponse(): void
    {
        $res = Response::redirect('/dashboard', 301);
        $this->assertInstanceOf(RedirectResponse::class, $res);
        $this->assertSame(301, $res->status());
        $this->assertSame('/dashboard', $res->headers()->get('location'));
    }

    public function testResponseRedirectFactoryDefaultStatus(): void
    {
        $res = Response::redirect('/home');
        $this->assertSame(302, $res->status());
    }

    public function testResponseRedirectFactoryPreservesCustomHeaders(): void
    {
        $res = Response::redirect('/home', 307, ['X-Reason' => 'temporary']);

        $this->assertSame(307, $res->status());
        $this->assertSame('/home', $res->headers()->get('location'));
        $this->assertSame('temporary', $res->headers()->get('x-reason'));
    }
}
