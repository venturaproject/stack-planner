<?php

declare(strict_types=1);

namespace App\Application\Shared\Storage;

interface FileStorageInterface
{
    public function store(string $directory, string $filename, string $temporaryPath): void;

    public function delete(string $directory, string $filename): void;
}
