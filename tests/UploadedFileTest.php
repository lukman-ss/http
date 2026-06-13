<?php

declare(strict_types=1);

namespace Lukman\Http\Tests;

use Lukman\Http\Request;
use Lukman\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    private function makeFile(
        string $name = 'photo.jpg',
        string $type = 'image/jpeg',
        string $tmpName = '/tmp/php12345',
        int $error = UPLOAD_ERR_OK,
        int $size = 1024,
    ): UploadedFile {
        return new UploadedFile($name, $type, $tmpName, $error, $size);
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function testName(): void
    {
        $this->assertSame('photo.jpg', $this->makeFile()->name());
    }

    public function testType(): void
    {
        $this->assertSame('image/jpeg', $this->makeFile()->type());
    }

    public function testTmpName(): void
    {
        $this->assertSame('/tmp/php12345', $this->makeFile()->tmpName());
    }

    public function testError(): void
    {
        $this->assertSame(UPLOAD_ERR_OK, $this->makeFile()->error());
    }

    public function testSize(): void
    {
        $this->assertSame(1024, $this->makeFile()->size());
    }

    // -------------------------------------------------------------------------
    // isValid()
    // -------------------------------------------------------------------------

    public function testIsValidReturnsTrueOnErrOk(): void
    {
        $this->assertTrue($this->makeFile(error: UPLOAD_ERR_OK)->isValid());
    }

    public function testIsValidReturnsFalseOnPartialError(): void
    {
        $this->assertFalse($this->makeFile(error: UPLOAD_ERR_PARTIAL)->isValid());
    }

    public function testIsValidReturnsFalseOnNoFile(): void
    {
        $this->assertFalse($this->makeFile(error: UPLOAD_ERR_NO_FILE)->isValid());
    }

    public function testIsValidReturnsFalseOnTooLarge(): void
    {
        $this->assertFalse($this->makeFile(error: UPLOAD_ERR_INI_SIZE)->isValid());
    }

    // -------------------------------------------------------------------------
    // extension()
    // -------------------------------------------------------------------------

    public function testExtensionFromJpg(): void
    {
        $this->assertSame('jpg', $this->makeFile('photo.jpg')->extension());
    }

    public function testExtensionFromPdf(): void
    {
        $this->assertSame('pdf', $this->makeFile('document.pdf')->extension());
    }

    public function testExtensionIsLowercase(): void
    {
        $this->assertSame('png', $this->makeFile('Image.PNG')->extension());
    }

    public function testExtensionFromDoubleExtension(): void
    {
        $this->assertSame('gz', $this->makeFile('archive.tar.gz')->extension());
    }

    public function testExtensionFromNoExtension(): void
    {
        $this->assertSame('', $this->makeFile('README')->extension());
    }

    public function testExtensionFromDotfileWithoutExtension(): void
    {
        $this->assertSame('', $this->makeFile('.env')->extension());
    }

    // -------------------------------------------------------------------------
    // moveTo() – using rename() fallback (non-uploaded files in test env)
    // -------------------------------------------------------------------------

    public function testMoveToReturnsFalseWhenInvalid(): void
    {
        $file = $this->makeFile(error: UPLOAD_ERR_PARTIAL);
        $this->assertFalse($file->moveTo(sys_get_temp_dir() . '/dest.jpg'));
    }

    public function testMoveToUsesRenameInTestEnvironment(): void
    {
        $src = tempnam(sys_get_temp_dir(), 'uft_');
        file_put_contents($src, 'test content');

        $dest = sys_get_temp_dir() . '/uft_dest_' . uniqid() . '.txt';

        $file = $this->makeFile(tmpName: $src);

        try {
            $result = $file->moveTo($dest);
            $this->assertTrue($result);
            $this->assertFileExists($dest);
        } finally {
            if (file_exists($dest)) {
                unlink($dest);
            }
            if (file_exists($src)) {
                unlink($src);
            }
        }
    }

    public function testMoveToReturnsFalseWhenSourceFileDoesNotExist(): void
    {
        $file = $this->makeFile(tmpName: sys_get_temp_dir() . '/missing_' . uniqid());

        $this->assertFalse($file->moveTo(sys_get_temp_dir() . '/dest_' . uniqid()));
    }
}
