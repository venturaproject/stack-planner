<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadedImageValidator
{
    private const int MAX_SIZE_BYTES = 8 * 1024 * 1024;

    /** @var array<string, string> */
    private const array MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function extension(UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            return null;
        }

        $size = $file->getSize();
        if (false === $size || $size > self::MAX_SIZE_BYTES) {
            return null;
        }

        $mimeType = $file->getMimeType();

        return null === $mimeType ? null : (self::MIME_EXTENSIONS[$mimeType] ?? null);
    }
}
