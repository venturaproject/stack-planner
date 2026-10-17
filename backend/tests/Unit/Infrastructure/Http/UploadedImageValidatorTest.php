<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http;

use App\Infrastructure\Http\UploadedImageValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadedImageValidatorTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testUsesDetectedImageMimeTypeInsteadOfTheClientFileExtension(): void
    {
        $file = $this->uploadedFile(
            'avatar.php',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jX5kAAAAASUVORK5CYII=', true) ?: '',
        );

        self::assertSame('png', (new UploadedImageValidator())->extension($file));
    }

    public function testRejectsNonImageContentEvenWhenTheClientClaimsItIsAnImage(): void
    {
        $file = $this->uploadedFile('avatar.jpg', '<?php echo "not an image";');

        self::assertNull((new UploadedImageValidator())->extension($file));
    }

    public function testRejectsImagesLargerThanEightMegabytes(): void
    {
        $pngHeader = "\x89PNG\r\n\x1a\n";
        $file = $this->uploadedFile('large.png', $pngHeader.str_repeat('x', 8 * 1024 * 1024));

        self::assertNull((new UploadedImageValidator())->extension($file));
    }

    private function uploadedFile(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'planner-image-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }
}
