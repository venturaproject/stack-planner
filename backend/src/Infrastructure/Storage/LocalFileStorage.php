<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Shared\Storage\FileStorageInterface;
use InvalidArgumentException;
use RuntimeException;

final class LocalFileStorage implements FileStorageInterface
{
    public function __construct(private readonly string $mediaDir)
    {
    }

    public function store(string $directory, string $filename, string $temporaryPath): void
    {
        $path = $this->directoryPath($directory);
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException(\sprintf('Unable to create media directory "%s".', $path));
        }

        if (!rename($temporaryPath, $this->filePath($directory, $filename))) {
            throw new RuntimeException('Unable to store uploaded media.');
        }
    }

    public function copy(string $directory, string $filename, string $sourcePath): void
    {
        $path = $this->directoryPath($directory);
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException(\sprintf('Unable to create media directory "%s".', $path));
        }

        if (!is_file($sourcePath) || !copy($sourcePath, $this->filePath($directory, $filename))) {
            throw new RuntimeException('Unable to copy media fixture.');
        }
    }

    public function read(string $directory, string $filename): ?string
    {
        $path = $this->filePath($directory, $filename);
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new RuntimeException('Unable to read stored media.');
        }

        return $contents;
    }

    public function exists(string $directory, string $filename): bool
    {
        return is_file($this->filePath($directory, $filename));
    }

    public function delete(string $directory, string $filename): void
    {
        $path = $this->filePath($directory, $filename);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Unable to delete stored media.');
        }
    }

    private function directoryPath(string $directory): string
    {
        $segments = explode('/', trim($directory, '/'));
        foreach ($segments as $segment) {
            if ('' === $segment || !preg_match('/\A[A-Za-z0-9_-]+\z/D', $segment)) {
                throw new InvalidArgumentException('Invalid media directory.');
            }
        }

        return rtrim($this->mediaDir, '/').'/'.implode('/', $segments);
    }

    private function filePath(string $directory, string $filename): string
    {
        if (!preg_match('/\A[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)?\z/D', $filename)) {
            throw new InvalidArgumentException('Invalid media filename.');
        }

        return $this->directoryPath($directory).'/'.$filename;
    }
}
