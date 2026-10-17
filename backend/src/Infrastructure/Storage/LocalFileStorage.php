<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Shared\Storage\FileStorageInterface;
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

        if (!rename($temporaryPath, $path.'/'.$filename)) {
            throw new RuntimeException('Unable to store uploaded media.');
        }
    }

    public function delete(string $directory, string $filename): void
    {
        $path = $this->directoryPath($directory).'/'.$filename;
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Unable to delete stored media.');
        }
    }

    private function directoryPath(string $directory): string
    {
        return rtrim($this->mediaDir, '/').'/'.trim($directory, '/');
    }
}
