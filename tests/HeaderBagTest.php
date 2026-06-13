<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use InvalidArgumentException;
use Lukman\Http\HeaderBag;
use PHPUnit\Framework\TestCase;

class HeaderBagTest extends TestCase
{
    // -------------------------------------------------------------------------
    // __construct
    // -------------------------------------------------------------------------

    public function testConstructorWithNoArguments(): void
    {
        $bag = new HeaderBag();
        $this->assertSame([], $bag->all());
    }

    public function testConstructorNormalizesKeys(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html', 'X-Custom' => 'foo']);
        $this->assertArrayHasKey('content-type', $bag->all());
        $this->assertArrayHasKey('x-custom', $bag->all());
    }

    public function testConstructorTrimsHeaderNames(): void
    {
        $bag = new HeaderBag([' Content-Type ' => 'text/html']);

        $this->assertSame('text/html', $bag->get('content-type'));
        $this->assertSame(['content-type' => 'text/html'], $bag->all());
    }

    public function testConstructorOverwritesDuplicateNormalizedNames(): void
    {
        $bag = new HeaderBag([
            'Content-Type' => 'text/html',
            ' content-type ' => 'application/json',
        ]);

        $this->assertSame('application/json', $bag->get('CONTENT-TYPE'));
        $this->assertCount(1, $bag->all());
    }

    public function testConstructorSetsValues(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'application/json']);
        $this->assertSame('application/json', $bag->get('Content-Type'));
    }

    public function testConstructorSupportsArrayValues(): void
    {
        $bag = new HeaderBag(['Accept' => ['text/html', 'application/json']]);
        $this->assertSame(['text/html', 'application/json'], $bag->get('accept'));
    }

    // -------------------------------------------------------------------------
    // set()
    // -------------------------------------------------------------------------

    public function testSetStoresStringValue(): void
    {
        $bag = new HeaderBag();
        $bag->set('Content-Type', 'text/plain');
        $this->assertSame('text/plain', $bag->get('content-type'));
    }

    public function testSetStoresArrayValue(): void
    {
        $bag = new HeaderBag();
        $bag->set('Accept', ['text/html', 'application/json']);
        $this->assertSame(['text/html', 'application/json'], $bag->get('accept'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $bag = new HeaderBag();
        $bag->set('Content-Type', 'text/plain');
        $bag->set('Content-Type', 'application/json');
        $this->assertSame('application/json', $bag->get('Content-Type'));
    }

    public function testSetOverwritesArrayWithString(): void
    {
        $bag = new HeaderBag();
        $bag->set('Accept', ['text/html', 'application/json']);
        $bag->set('Accept', 'text/plain');
        $this->assertSame('text/plain', $bag->get('accept'));
    }

    public function testSetIsCaseInsensitive(): void
    {
        $bag = new HeaderBag();
        $bag->set('content-type', 'text/html');
        $bag->set('CONTENT-TYPE', 'text/plain');
        $this->assertSame('text/plain', $bag->get('Content-Type'));
        // Only one key should exist
        $this->assertCount(1, $bag->all());
    }

    public function testSetTrimsHeaderNameBeforeOverwrite(): void
    {
        $bag = new HeaderBag();
        $bag->set(' Content-Type ', 'text/html');
        $bag->set('content-type', 'application/json');

        $this->assertSame('application/json', $bag->get(' CONTENT-TYPE '));
        $this->assertSame(['content-type' => 'application/json'], $bag->all());
    }

    public function testSetReindexesArrayValues(): void
    {
        $bag = new HeaderBag();
        $bag->set('Accept', [2 => 'text/html', 4 => 'application/json']);

        $this->assertSame(['text/html', 'application/json'], $bag->get('accept'));
    }

    public function testSetRejectsNonStringArrayValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be strings.');

        $bag = new HeaderBag();
        $bag->set('Accept', ['text/html', 10]);
    }

    // -------------------------------------------------------------------------
    // add()
    // -------------------------------------------------------------------------

    public function testAddCreatesHeaderWhenAbsent(): void
    {
        $bag = new HeaderBag();
        $bag->add('X-Custom', 'first');
        $this->assertSame('first', $bag->get('X-Custom'));
    }

    public function testAddConvertsExistingStringToArray(): void
    {
        $bag = new HeaderBag();
        $bag->set('Accept', 'text/html');
        $bag->add('Accept', 'application/json');
        $this->assertSame(['text/html', 'application/json'], $bag->get('accept'));
    }

    public function testAddAppendsToExistingArray(): void
    {
        $bag = new HeaderBag();
        $bag->set('Accept', ['text/html', 'application/json']);
        $bag->add('Accept', 'text/xml');
        $this->assertSame(['text/html', 'application/json', 'text/xml'], $bag->get('accept'));
    }

    public function testAddIsCaseInsensitive(): void
    {
        $bag = new HeaderBag();
        $bag->set('accept', 'text/html');
        $bag->add('ACCEPT', 'application/json');
        $this->assertSame(['text/html', 'application/json'], $bag->get('Accept'));
    }

    public function testAddTrimsHeaderNameAndPreservesOldValue(): void
    {
        $bag = new HeaderBag();
        $bag->set(' Accept ', 'text/html');
        $bag->add(' ACCEPT ', 'application/json');

        $this->assertSame(['text/html', 'application/json'], $bag->get('accept'));
        $this->assertCount(1, $bag->all());
    }

    public function testAddDoesNotAffectOtherHeaders(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/plain']);
        $bag->add('Accept', 'text/html');
        $this->assertSame('text/plain', $bag->get('content-type'));
    }

    // -------------------------------------------------------------------------
    // get()
    // -------------------------------------------------------------------------

    public function testGetReturnsNullByDefaultWhenMissing(): void
    {
        $bag = new HeaderBag();
        $this->assertNull($bag->get('Missing-Header'));
    }

    public function testGetReturnsCustomDefaultWhenMissing(): void
    {
        $bag = new HeaderBag();
        $this->assertSame('default-value', $bag->get('Missing-Header', 'default-value'));
    }

    public function testGetReturnsFalsyDefaultWhenMissing(): void
    {
        $bag = new HeaderBag();

        $this->assertSame('', $bag->get('Missing-Header', ''));
        $this->assertSame(false, $bag->get('Missing-Header', false));
        $this->assertSame(0, $bag->get('Missing-Header', 0));
    }

    public function testGetTrimsHeaderName(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);

        $this->assertSame('text/html', $bag->get(' content-type '));
    }

    public function testGetIsCaseInsensitiveForContentType(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);

        $this->assertSame('text/html', $bag->get('Content-Type'));
        $this->assertSame('text/html', $bag->get('content-type'));
        $this->assertSame('text/html', $bag->get('CONTENT-TYPE'));
    }

    public function testGetReturnsStringAsString(): void
    {
        $bag = new HeaderBag(['X-Foo' => 'bar']);
        $result = $bag->get('x-foo');
        $this->assertIsString($result);
        $this->assertSame('bar', $result);
    }

    public function testGetReturnsArrayAsArray(): void
    {
        $bag = new HeaderBag(['Accept' => ['text/html', 'application/json']]);
        $result = $bag->get('accept');
        $this->assertIsArray($result);
        $this->assertSame(['text/html', 'application/json'], $result);
    }

    // -------------------------------------------------------------------------
    // all()
    // -------------------------------------------------------------------------

    public function testAllReturnsEmptyArrayWhenEmpty(): void
    {
        $bag = new HeaderBag();
        $this->assertSame([], $bag->all());
    }

    public function testAllReturnsAllHeadersWithNormalizedKeys(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html', 'X-Custom' => 'test']);
        $all = $bag->all();

        $this->assertArrayHasKey('content-type', $all);
        $this->assertArrayHasKey('x-custom', $all);
        $this->assertCount(2, $all);
    }

    // -------------------------------------------------------------------------
    // has()
    // -------------------------------------------------------------------------

    public function testHasReturnsTrueForExistingHeader(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);
        $this->assertTrue($bag->has('Content-Type'));
    }

    public function testHasReturnsFalseForMissingHeader(): void
    {
        $bag = new HeaderBag();
        $this->assertFalse($bag->has('X-Missing'));
    }

    public function testHasIsCaseInsensitive(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);
        $this->assertTrue($bag->has('content-type'));
        $this->assertTrue($bag->has('CONTENT-TYPE'));
        $this->assertTrue($bag->has('Content-Type'));
    }

    public function testHasTrimsHeaderName(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);

        $this->assertTrue($bag->has(' content-type '));
    }

    // -------------------------------------------------------------------------
    // remove()
    // -------------------------------------------------------------------------

    public function testRemoveDeletesExistingHeader(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);
        $bag->remove('Content-Type');
        $this->assertFalse($bag->has('content-type'));
    }

    public function testRemoveIsCaseInsensitive(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);
        $bag->remove('CONTENT-TYPE');
        $this->assertFalse($bag->has('Content-Type'));
    }

    public function testRemoveTrimsHeaderName(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);
        $bag->remove(' content-type ');

        $this->assertFalse($bag->has('Content-Type'));
    }

    public function testRemoveOnNonExistentHeaderDoesNotThrow(): void
    {
        $bag = new HeaderBag();
        // Should not throw
        $bag->remove('X-Missing');
        $this->assertFalse($bag->has('X-Missing'));
    }

    public function testRemoveDoesNotAffectOtherHeaders(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html', 'Accept' => 'text/plain']);
        $bag->remove('Content-Type');
        $this->assertTrue($bag->has('accept'));
        $this->assertSame('text/plain', $bag->get('Accept'));
    }

    // -------------------------------------------------------------------------
    // replace()
    // -------------------------------------------------------------------------

    public function testReplaceOverwritesAllHeaders(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html', 'Accept' => 'text/plain']);
        $bag->replace(['X-New' => 'value']);
        $this->assertFalse($bag->has('Content-Type'));
        $this->assertFalse($bag->has('Accept'));
        $this->assertTrue($bag->has('X-New'));
        $this->assertSame('value', $bag->get('x-new'));
    }

    public function testReplaceClearsOldHeadersBeforeApplyingNewNormalizedHeaders(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html', 'Accept' => 'text/plain']);
        $bag->replace([' ACCEPT ' => 'application/json']);

        $this->assertSame(['accept' => 'application/json'], $bag->all());
        $this->assertSame('missing', $bag->get('content-type', 'missing'));
    }

    public function testReplaceWithEmptyArrayClearsAll(): void
    {
        $bag = new HeaderBag(['Content-Type' => 'text/html']);
        $bag->replace([]);
        $this->assertSame([], $bag->all());
    }

    public function testReplaceNormalizesKeys(): void
    {
        $bag = new HeaderBag();
        $bag->replace(['Content-Type' => 'text/html', 'X-CUSTOM' => 'test']);
        $all = $bag->all();
        $this->assertArrayHasKey('content-type', $all);
        $this->assertArrayHasKey('x-custom', $all);
    }

    public function testReplaceSupportsArrayValues(): void
    {
        $bag = new HeaderBag();
        $bag->replace(['Accept' => ['text/html', 'application/json']]);
        $this->assertSame(['text/html', 'application/json'], $bag->get('accept'));
    }

    public function testReplaceRejectsNonStringArrayValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be strings.');

        $bag = new HeaderBag();
        $bag->replace(['Accept' => ['text/html', 10]]);
    }
}
