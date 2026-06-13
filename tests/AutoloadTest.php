<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use PHPUnit\Framework\TestCase;
use Lukman\Http\HeaderBag;
use Lukman\Http\JsonResponse;
use Lukman\Http\MiddlewareInterface;
use Lukman\Http\MiddlewarePipeline;
use Lukman\Http\RedirectResponse;
use Lukman\Http\Request;
use Lukman\Http\RequestHandlerInterface;
use Lukman\Http\Response;
use Lukman\Http\UploadedFile;

class AutoloadTest extends TestCase
{
    public function testPackageClassesCanBeAutoloaded(): void
    {
        $classes = [
            HeaderBag::class,
            JsonResponse::class,
            MiddlewarePipeline::class,
            RedirectResponse::class,
            Request::class,
            Response::class,
            UploadedFile::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), $class . ' was not autoloaded.');
        }
    }

    public function testPackageInterfacesCanBeAutoloaded(): void
    {
        $interfaces = [
            MiddlewareInterface::class,
            RequestHandlerInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $this->assertTrue(interface_exists($interface), $interface . ' was not autoloaded.');
        }
    }
}
