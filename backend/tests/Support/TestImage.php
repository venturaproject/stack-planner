<?php

declare(strict_types=1);

namespace App\Tests\Support;

final class TestImage
{
    public static function onePixelPng(): string
    {
        return \base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jX5kAAAAASUVORK5CYII=', true)
            ?: throw new \RuntimeException('Invalid PNG fixture.');
    }
}
