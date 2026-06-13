<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use Lukman\Http\HeaderBag;
use Lukman\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function make(
        string $method = 'GET',
        string $uri = '/foo/bar',
        array $query = [],
        array $request = [],
        array $headers = [],
        mixed $body = null,
    ): Request {
        return new Request($method, $uri, $query, $request, $headers, $body);
    }

    // -------------------------------------------------------------------------
    // method()
    // -------------------------------------------------------------------------

    public function testMethodIsStoredUppercase(): void
    {
        $req = $this->make('get');
        $this->assertSame('GET', $req->method());
    }

    public function testMethodMixedCaseIsNormalized(): void
    {
        $req = $this->make('pOsT');
        $this->assertSame('POST', $req->method());
    }

    public function testMethodIsTrimmedAndNormalized(): void
    {
        $req = $this->make(' post ');
        $this->assertSame('POST', $req->method());
    }

    public function testMethodDelete(): void
    {
        $req = $this->make('DELETE');
        $this->assertSame('DELETE', $req->method());
    }

    // -------------------------------------------------------------------------
    // uri()
    // -------------------------------------------------------------------------

    public function testUriIsReturnedAsGiven(): void
    {
        $req = $this->make('GET', 'https://example.com/foo?bar=baz');
        $this->assertSame('https://example.com/foo?bar=baz', $req->uri());
    }

    public function testUriWithoutQueryString(): void
    {
        $req = $this->make('GET', '/simple/path');
        $this->assertSame('/simple/path', $req->uri());
    }

    // -------------------------------------------------------------------------
    // path()
    // -------------------------------------------------------------------------

    public function testPathStripsQueryString(): void
    {
        $req = $this->make('GET', '/users/profile?tab=settings&page=2');
        $this->assertSame('/users/profile', $req->path());
    }

    public function testPathWithoutQueryString(): void
    {
        $req = $this->make('GET', '/about');
        $this->assertSame('/about', $req->path());
    }

    public function testPathFromFullUrl(): void
    {
        $req = $this->make('GET', 'https://example.com/api/v1/users?sort=asc');
        $this->assertSame('/api/v1/users', $req->path());
    }

    public function testPathOnRootUri(): void
    {
        $req = $this->make('GET', '/');
        $this->assertSame('/', $req->path());
    }

    public function testPathReturnsRootWhenUriOnlyContainsQueryString(): void
    {
        $req = $this->make('GET', '?page=2');
        $this->assertSame('/', $req->path());
    }

    public function testPathFromCapturedUriWithQueryString(): void
    {
        $req = $this->make('GET', '/api/users?active=1');
        $this->assertSame('/api/users', $req->path());
    }

    // -------------------------------------------------------------------------
    // query()
    // -------------------------------------------------------------------------

    public function testQueryWithoutKeyReturnsAllParams(): void
    {
        $req = $this->make('GET', '/search', ['q' => 'hello', 'page' => '2']);
        $this->assertSame(['q' => 'hello', 'page' => '2'], $req->query());
    }

    public function testQueryWithKeyReturnsValue(): void
    {
        $req = $this->make('GET', '/search', ['q' => 'world']);
        $this->assertSame('world', $req->query('q'));
    }

    public function testQueryWithMissingKeyReturnsNull(): void
    {
        $req = $this->make('GET', '/search', ['q' => 'world']);
        $this->assertNull($req->query('missing'));
    }

    public function testQueryWithMissingKeyReturnsCustomDefault(): void
    {
        $req = $this->make('GET', '/search');
        $this->assertSame('fallback', $req->query('page', 'fallback'));
    }

    public function testQueryWithMissingKeyReturnsFalsyDefault(): void
    {
        $req = $this->make('GET', '/search');
        $this->assertSame('', $req->query('page', ''));
        $this->assertSame(false, $req->query('page', false));
        $this->assertSame(0, $req->query('page', 0));
    }

    public function testQueryWithNullKeyOnEmptyParamsReturnsEmptyArray(): void
    {
        $req = $this->make('GET', '/');
        $this->assertSame([], $req->query());
    }

    // -------------------------------------------------------------------------
    // input()
    // -------------------------------------------------------------------------

    public function testInputWithoutKeyReturnsAllParams(): void
    {
        $req = $this->make('POST', '/login', [], ['username' => 'lukman', 'password' => 'secret']);
        $this->assertSame(['username' => 'lukman', 'password' => 'secret'], $req->input());
    }

    public function testInputWithKeyReturnsValue(): void
    {
        $req = $this->make('POST', '/login', [], ['username' => 'lukman']);
        $this->assertSame('lukman', $req->input('username'));
    }

    public function testInputWithMissingKeyReturnsNull(): void
    {
        $req = $this->make('POST', '/login', [], ['username' => 'lukman']);
        $this->assertNull($req->input('missing'));
    }

    public function testInputWithMissingKeyReturnsCustomDefault(): void
    {
        $req = $this->make('POST', '/register', []);
        $this->assertSame('default@example.com', $req->input('email', 'default@example.com'));
    }

    public function testInputWithMissingKeyReturnsFalsyDefault(): void
    {
        $req = $this->make('POST', '/register', []);
        $this->assertSame('', $req->input('email', ''));
        $this->assertSame(false, $req->input('email', false));
        $this->assertSame(0, $req->input('email', 0));
    }

    public function testInputWithNullKeyOnEmptyParamsReturnsEmptyArray(): void
    {
        $req = $this->make('POST', '/submit');
        $this->assertSame([], $req->input());
    }

    // -------------------------------------------------------------------------
    // header() and headers()
    // -------------------------------------------------------------------------

    public function testHeaderReturnsValueCaseInsensitive(): void
    {
        $req = $this->make('GET', '/', [], [], ['Content-Type' => 'application/json']);
        $this->assertSame('application/json', $req->header('Content-Type'));
        $this->assertSame('application/json', $req->header('content-type'));
        $this->assertSame('application/json', $req->header('CONTENT-TYPE'));
    }

    public function testHeaderReturnsnullWhenMissing(): void
    {
        $req = $this->make('GET', '/');
        $this->assertNull($req->header('X-Missing'));
    }

    public function testHeaderReturnsCustomDefaultWhenMissing(): void
    {
        $req = $this->make('GET', '/');
        $this->assertSame('fallback', $req->header('X-Missing', 'fallback'));
    }

    public function testHeadersReturnsHeaderBagInstance(): void
    {
        $req = $this->make('GET', '/', [], [], ['Accept' => 'text/html']);
        $this->assertInstanceOf(HeaderBag::class, $req->headers());
    }

    public function testHeaderReadsFromHeaderBagNormalization(): void
    {
        $req = $this->make('GET', '/', [], [], [' Content-Type ' => 'application/json']);

        $this->assertSame('application/json', $req->header('CONTENT-TYPE'));
        $this->assertSame(['content-type' => 'application/json'], $req->headers()->all());
    }

    public function testHeadersReflectsStoredValues(): void
    {
        $req = $this->make('GET', '/', [], [], ['Accept' => 'text/html']);
        $this->assertTrue($req->headers()->has('accept'));
        $this->assertSame('text/html', $req->headers()->get('Accept'));
    }

    // -------------------------------------------------------------------------
    // body()
    // -------------------------------------------------------------------------

    public function testBodyIsNullByDefault(): void
    {
        $req = $this->make('GET', '/');
        $this->assertNull($req->body());
    }

    public function testBodyReturnsStringRawBody(): void
    {
        $req = $this->make('POST', '/', [], [], [], '{"key":"value"}');
        $this->assertSame('{"key":"value"}', $req->body());
    }

    public function testBodyCanBeArray(): void
    {
        $data = ['key' => 'value'];
        $req  = $this->make('POST', '/', [], [], [], $data);
        $this->assertSame($data, $req->body());
    }

    // -------------------------------------------------------------------------
    // isMethod()
    // -------------------------------------------------------------------------

    public function testIsMethodReturnsTrueForMatchingMethod(): void
    {
        $req = $this->make('GET');
        $this->assertTrue($req->isMethod('GET'));
    }

    public function testIsMethodIsCaseInsensitive(): void
    {
        $req = $this->make('POST');
        $this->assertTrue($req->isMethod('post'));
        $this->assertTrue($req->isMethod('Post'));
        $this->assertTrue($req->isMethod('POST'));
    }

    public function testIsMethodTrimsComparedMethod(): void
    {
        $req = $this->make(' POST ');
        $this->assertTrue($req->isMethod(' post '));
    }

    public function testIsMethodReturnsFalseForNonMatchingMethod(): void
    {
        $req = $this->make('GET');
        $this->assertFalse($req->isMethod('POST'));
        $this->assertFalse($req->isMethod('PUT'));
        $this->assertFalse($req->isMethod('DELETE'));
    }

    // -------------------------------------------------------------------------
    // json()
    // -------------------------------------------------------------------------

    public function testJsonReturnsDecodedArrayWhenContentTypeIsJson(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            [],
            ['Content-Type' => 'application/json'],
            '{"name":"lukman","active":true}',
        );

        $this->assertSame(['name' => 'lukman', 'active' => true], $req->json());
        $this->assertSame('lukman', $req->json('name'));
    }

    public function testJsonDoesNotParseWhenContentTypeIsNotJson(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            [],
            ['Content-Type' => 'text/plain'],
            '{"name":"lukman"}',
        );

        $this->assertSame([], $req->json());
        $this->assertSame('fallback', $req->json('name', 'fallback'));
    }

    public function testJsonReturnsEmptyArrayForInvalidJson(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            [],
            ['Content-Type' => 'application/json'],
            '{"name": invalid}',
        );

        $this->assertSame([], $req->json());
        $this->assertSame('fallback', $req->json('name', 'fallback'));
    }

    public function testJsonReturnsDefaultForMissingKey(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            [],
            ['Content-Type' => 'application/json'],
            '{"name":"lukman"}',
        );

        $this->assertSame('fallback', $req->json('email', 'fallback'));
    }

    public function testJsonContentTypeIsCaseInsensitive(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            [],
            ['CONTENT-TYPE' => 'APPLICATION/JSON; charset=utf-8'],
            '{"name":"lukman"}',
        );

        $this->assertSame('lukman', $req->json('name'));
    }

    // -------------------------------------------------------------------------
    // all(), only(), except()
    // -------------------------------------------------------------------------

    public function testAllMergesInputAndJsonWithJsonTakingPrecedence(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            ['name' => 'input-name', 'email' => 'mail@example.com'],
            ['Content-Type' => 'application/json'],
            '{"name":"json-name","active":true}',
        );

        $this->assertSame([
            'name' => 'json-name',
            'email' => 'mail@example.com',
            'active' => true,
        ], $req->all());
    }

    public function testAllReturnsOnlyInputWhenJsonIsInvalid(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            ['name' => 'lukman'],
            ['Content-Type' => 'application/json'],
            '{invalid}',
        );

        $this->assertSame(['name' => 'lukman'], $req->all());
    }

    public function testOnlyReturnsExistingKeysOnly(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            ['name' => 'lukman'],
            ['Content-Type' => 'application/json'],
            '{"active":true}',
        );

        $this->assertSame(['name' => 'lukman', 'active' => true], $req->only(['name', 'missing', 'active']));
    }

    public function testExceptRemovesGivenKeys(): void
    {
        $req = $this->make(
            'POST',
            '/',
            [],
            ['name' => 'lukman', 'email' => 'mail@example.com'],
            ['Content-Type' => 'application/json'],
            '{"active":true}',
        );

        $this->assertSame(['name' => 'lukman', 'active' => true], $req->except(['email', 'missing']));
    }

    // -------------------------------------------------------------------------
    // capture()
    // -------------------------------------------------------------------------

    public function testCaptureUsesExplicitSources(): void
    {
        $req = Request::capture(
            server: [
                'REQUEST_METHOD' => 'PATCH',
                'REQUEST_URI' => '/api/v1/update?id=123',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            ],
            query: ['id' => '123'],
            request: ['foo' => 'bar'],
            files: [],
            body: '{"ok":true}',
        );

        $this->assertSame('PATCH', $req->method());
        $this->assertSame('/api/v1/update?id=123', $req->uri());
        $this->assertSame(['id' => '123'], $req->query());
        $this->assertSame(['foo' => 'bar'], $req->input());
        $this->assertSame('XMLHttpRequest', $req->header('X-Requested-With'));
        $this->assertSame('application/json', $req->header('Content-Type'));
        $this->assertSame('{"ok":true}', $req->body());
    }

    public function testCaptureDefaults(): void
    {
        $req = Request::capture(server: [], query: [], request: [], files: [], body: '');

        $this->assertSame('GET', $req->method());
        $this->assertSame('/', $req->uri());
        $this->assertSame([], $req->query());
        $this->assertSame([], $req->input());
        $this->assertSame('', $req->body());
    }

    public function testCaptureUsesSafeDefaultsWhenServerValuesAreNotStrings(): void
    {
        $req = Request::capture(
            server: ['REQUEST_METHOD' => ['POST'], 'REQUEST_URI' => false],
            query: [],
            request: [],
            files: [],
            body: '',
        );

        $this->assertSame('GET', $req->method());
        $this->assertSame('/', $req->uri());
    }

    public function testCaptureNormalizesUploadedFiles(): void
    {
        $req = Request::capture(
            server: [],
            query: [],
            request: [],
            files: [
                'avatar' => [
                    'name' => 'avatar.png',
                    'type' => 'image/png',
                    'tmp_name' => '/tmp/avatar',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 100,
                ],
            ],
            body: '',
        );

        $this->assertInstanceOf(\Lukman\Http\UploadedFile::class, $req->file('avatar'));
    }

    // -------------------------------------------------------------------------
    // extractHeadersFromServer()
    // -------------------------------------------------------------------------

    public function testExtractHeadersFromServerProcessesHttpKeys(): void
    {
        $server = [
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'PHPUnit',
            'HTTP_X_CUSTOM_HEADER' => 'Value',
            'SOME_OTHER_KEY' => 'ignored',
        ];

        $extracted = Request::extractHeadersFromServer($server);

        $this->assertArrayHasKey('HOST', $extracted);
        $this->assertSame('localhost', $extracted['HOST']);

        $this->assertArrayHasKey('USER-AGENT', $extracted);
        $this->assertSame('PHPUnit', $extracted['USER-AGENT']);

        $this->assertArrayHasKey('X-CUSTOM-HEADER', $extracted);
        $this->assertSame('Value', $extracted['X-CUSTOM-HEADER']);

        $this->assertArrayNotHasKey('SOME_OTHER_KEY', $extracted);
    }

    public function testExtractHeadersFromServerProcessesContentTypesAndLength(): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '123',
            'CONTENT_MD5' => 'abc',
        ];

        $extracted = Request::extractHeadersFromServer($server);

        $this->assertArrayHasKey('CONTENT-TYPE', $extracted);
        $this->assertSame('application/json', $extracted['CONTENT-TYPE']);

        $this->assertArrayHasKey('CONTENT-LENGTH', $extracted);
        $this->assertSame('123', $extracted['CONTENT-LENGTH']);

        $this->assertArrayHasKey('CONTENT-MD5', $extracted);
        $this->assertSame('abc', $extracted['CONTENT-MD5']);
    }

    public function testExtractHeadersFromServerProcessesHttpContentTypeWithoutHttpPrefix(): void
    {
        $extracted = Request::extractHeadersFromServer([
            'HTTP_CONTENT_TYPE' => 'application/json',
        ]);

        $this->assertArrayHasKey('CONTENT-TYPE', $extracted);
        $this->assertArrayNotHasKey('HTTP-CONTENT-TYPE', $extracted);
        $this->assertSame('application/json', $extracted['CONTENT-TYPE']);
    }

    // -------------------------------------------------------------------------
    // file()
    // -------------------------------------------------------------------------

    private function makeUploadedFile(string $name = 'avatar.png'): \Lukman\Http\UploadedFile
    {
        return new \Lukman\Http\UploadedFile($name, 'image/png', '/tmp/phpabc', UPLOAD_ERR_OK, 512);
    }

    public function testFileReturnsAllFilesWhenKeyIsNull(): void
    {
        $avatar = $this->makeUploadedFile('avatar.png');
        $cv     = $this->makeUploadedFile('cv.pdf');

        $req = new Request('POST', '/', [], [], [], null, ['avatar' => $avatar, 'cv' => $cv]);
        $this->assertSame(['avatar' => $avatar, 'cv' => $cv], $req->file());
    }

    public function testFileReturnsSpecificFileByKey(): void
    {
        $avatar = $this->makeUploadedFile('avatar.png');
        $req    = new Request('POST', '/', [], [], [], null, ['avatar' => $avatar]);
        $this->assertSame($avatar, $req->file('avatar'));
    }

    public function testFileReturnsnullWhenKeyMissing(): void
    {
        $req = new Request('POST', '/', [], [], [], null, []);
        $this->assertNull($req->file('missing'));
    }

    public function testFileReturnsCustomDefaultWhenKeyMissing(): void
    {
        $req = new Request('POST', '/', [], [], [], null, []);
        $this->assertSame('fallback', $req->file('missing', 'fallback'));
    }

    public function testFileReturnsEmptyArrayWhenNoFiles(): void
    {
        $req = new Request('POST', '/');
        $this->assertSame([], $req->file());
    }

    public function testConstructorAcceptsRawFileArraysForBackwardCompatibility(): void
    {
        $req = new Request('POST', '/', [], [], [], null, [
            'avatar' => [
                'name' => 'avatar.png',
                'type' => 'image/png',
                'tmp_name' => '/tmp/avatar',
                'error' => UPLOAD_ERR_OK,
                'size' => 100,
            ],
        ]);

        $file = $req->file('avatar');

        $this->assertInstanceOf(\Lukman\Http\UploadedFile::class, $file);
        $this->assertSame('avatar.png', $file->name());
    }

    public function testConstructorSkipsInvalidFileEntries(): void
    {
        $req = new Request('POST', '/', [], [], [], null, ['avatar' => 'not-a-file']);

        $this->assertSame([], $req->file());
    }

    // -------------------------------------------------------------------------
    // normalizeFiles()
    // -------------------------------------------------------------------------

    public function testNormalizeFilesConvertsToUploadedFileInstances(): void
    {
        $raw = [
            'avatar' => [
                'name'     => 'photo.jpg',
                'type'     => 'image/jpeg',
                'tmp_name' => '/tmp/phpXXXX',
                'error'    => UPLOAD_ERR_OK,
                'size'     => 2048,
            ],
        ];

        $result = Request::normalizeFiles($raw);

        $this->assertArrayHasKey('avatar', $result);
        $this->assertInstanceOf(\Lukman\Http\UploadedFile::class, $result['avatar']);
        $this->assertSame('photo.jpg', $result['avatar']->name());
        $this->assertSame('image/jpeg', $result['avatar']->type());
        $this->assertSame('/tmp/phpXXXX', $result['avatar']->tmpName());
        $this->assertSame(UPLOAD_ERR_OK, $result['avatar']->error());
        $this->assertSame(2048, $result['avatar']->size());
    }

    public function testNormalizeFilesSkipsIncompleteEntries(): void
    {
        $raw = [
            'bad' => ['name' => 'file.txt'],         // missing other keys
            'ok'  => [
                'name' => 'ok.txt', 'type' => 'text/plain',
                'tmp_name' => '/tmp/ok', 'error' => UPLOAD_ERR_OK, 'size' => 10,
            ],
        ];

        $result = Request::normalizeFiles($raw);
        $this->assertArrayNotHasKey('bad', $result);
        $this->assertArrayHasKey('ok', $result);
    }

    public function testNormalizeFilesReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], Request::normalizeFiles([]));
    }
}
