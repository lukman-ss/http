<?php

declare(strict_types=1);

namespace Lukman\Http;

class UploadedFile
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly string $tmpName,
        private readonly int $error,
        private readonly int $size,
    ) {}

    /**
     * Original client-provided file name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Client-provided MIME type.
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Temporary file path on the server.
     */
    public function tmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * PHP upload error code.
     */
    public function error(): int
    {
        return $this->error;
    }

    /**
     * File size in bytes.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * True when the file uploaded without error.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Extension derived from the original client file name (lowercase, without dot).
     */
    public function extension(): string
    {
        if (str_starts_with($this->name, '.') && substr_count($this->name, '.') === 1) {
            return '';
        }

        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * Move the uploaded file to $targetPath.
     *
     * In a real HTTP context, uses move_uploaded_file().
     * Falls back to rename() for testability (e.g. when the tmp file is not
     * a genuine PHP upload stream).
     */
    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if (is_uploaded_file($this->tmpName)) {
            return move_uploaded_file($this->tmpName, $targetPath);
        }

        if (!is_file($this->tmpName)) {
            return false;
        }

        // Fallback for non-HTTP contexts (CLI, unit tests).
        return rename($this->tmpName, $targetPath);
    }
}
