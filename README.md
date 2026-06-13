# lukman-ss/http

A clean, modern, and dependency-free PHP 8.2+ HTTP abstraction package.

## Requirements

- PHP 8.2 or higher
- No runtime dependencies

## Installation

```bash
composer require lukman-ss/http
```

## Request

```php
use Lukman\Http\Request;

$request = new Request(
    method: 'POST',
    uri: '/api/users?page=2',
    query: ['page' => '2'],
    request: ['name' => 'Lukman', 'email' => 'lukman@example.com'],
    headers: ['Content-Type' => 'application/json'],
    body: '{"name":"Lukman"}',
);

$request->method();         // 'POST'
$request->uri();            // '/api/users?page=2'
$request->path();           // '/api/users'
$request->isMethod('post'); // true

$request->query('page');    // '2'
$request->query();          // ['page' => '2']
$request->input('name');    // 'Lukman'
$request->input();          // ['name' => 'Lukman', 'email' => 'lukman@example.com']

$request->json('name');     // 'Lukman'
$request->json();           // ['name' => 'Lukman']

$request->all();             // ['name' => 'Lukman', 'email' => 'lukman@example.com']
$request->only(['name']);    // ['name' => 'Lukman']
$request->except(['email']); // ['name' => 'Lukman']

$request->header('Content-Type');   // 'application/json'
$request->header('content-type');   // 'application/json'
$request->headers()->has('accept'); // false
```

Capture from PHP globals:

```php
$request = Request::capture();
```

Uploaded files:

```php
$file = $request->file('avatar'); // UploadedFile|null

$file?->isValid();
$file?->extension();
$file?->moveTo('/var/uploads/avatar.jpg');
```

## Response

```php
use Lukman\Http\Response;

$response = new Response('Hello, world!', 200, ['X-App' => 'lukman-http']);

$response->content(); // 'Hello, world!'
$response->status();  // 200

$response
    ->withStatus(201)
    ->setContent('Created')
    ->header('Location', '/api/users/1');

$response->send();
```

## JsonResponse

```php
use Lukman\Http\JsonResponse;
use Lukman\Http\Response;

$response = new JsonResponse(['id' => 1, 'name' => 'Lukman'], 201);

$response->content();                      // '{"id":1,"name":"Lukman"}'
$response->headers()->get('content-type'); // 'application/json'

$response = Response::json(['error' => 'not found'], 404);
```

## RedirectResponse

```php
use Lukman\Http\RedirectResponse;
use Lukman\Http\Response;

$response = new RedirectResponse('/dashboard');

$response->headers()->get('location'); // '/dashboard'
$response->status();                   // 302

$response = new RedirectResponse('/new-url', 301);
$response = Response::redirect('/login', 302);
```

## MiddlewarePipeline

```php
use Lukman\Http\MiddlewareInterface;
use Lukman\Http\MiddlewarePipeline;
use Lukman\Http\Request;
use Lukman\Http\RequestHandlerInterface;
use Lukman\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($request->header('Authorization') !== 'Bearer secret') {
            return new Response('Unauthorized', 401);
        }

        return $handler->handle($request);
    }
}

class FinalHandler implements RequestHandlerInterface
{
    public function handle(Request $request): Response
    {
        return new Response('OK', 200);
    }
}

$pipeline = new MiddlewarePipeline([new AuthMiddleware()], new FinalHandler());
$response = $pipeline->handle(Request::capture());
```

## Project Structure

```text
src/
  HeaderBag.php
  JsonResponse.php
  MiddlewareInterface.php
  MiddlewarePipeline.php
  RedirectResponse.php
  Request.php
  RequestHandlerInterface.php
  Response.php
  UploadedFile.php
tests/
  AutoloadTest.php
  HeaderBagTest.php
  JsonResponseTest.php
  MiddlewarePipelineTest.php
  RedirectResponseTest.php
  RequestTest.php
  ResponseTest.php
  UploadedFileTest.php
```

## Running Tests

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).
