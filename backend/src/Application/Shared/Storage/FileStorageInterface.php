<?php

declare(strict_types=1);

namespace App\Application\Shared\Storage;

interface FileStorageInterface
{
    public function store(string $directory, string $filename, string $temporaryPath): void;

    public function copy(string $directory, string $filename, string $sourcePath): void;

    public function read(string $directory, string $filename): ?string;

    public function exists(string $directory, string $filename): bool;

    public function delete(string $directory, string $filename): void;
}
