<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\UploadAvatarCommand;
use App\Application\Auth\Command\UploadAvatarHandler;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Infrastructure\Storage\LocalFileStorage;
use App\Tests\Support\EntityIdSetter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UploadAvatarHandlerTest extends TestCase
{
    use EntityIdSetter;

    private string $mediaDir;

    protected function setUp(): void
    {
        $this->mediaDir = sys_get_temp_dir().'/avatar-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        $avatars = $this->mediaDir.'/avatars';
        if (is_dir($avatars)) {
            foreach (glob($avatars.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($avatars);
        }
        if (is_dir($this->mediaDir)) {
            rmdir($this->mediaDir);
        }
    }

    public function testMovesFileAndUpdatesAvatarUrl(): void
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        self::assertNotFalse($tmpFile);

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->method('save')->willReturnArgument(0);

        $handler = new UploadAvatarHandler($users, new LocalFileStorage($this->mediaDir));

        $response = $handler->handle(new UploadAvatarCommand('user-1', $tmpFile, 'PNG'));

        self::assertSame('/media/avatars/user-1.png', $response->avatarUrl);
        self::assertFileExists($this->mediaDir.'/avatars/user-1.png');
    }

    public function testRejectsDisallowedExtension(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);

        $handler = new UploadAvatarHandler($users, new LocalFileStorage($this->mediaDir));

        $this->expectException(InvalidArgumentException::class);

        $handler->handle(new UploadAvatarCommand('user-1', '/tmp/whatever', 'exe'));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        self::assertNotFalse($tmpFile);

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $handler = new UploadAvatarHandler($users, new LocalFileStorage($this->mediaDir));

        $this->expectException(UserNotFoundException::class);

        try {
            $handler->handle(new UploadAvatarCommand('missing', $tmpFile, 'png'));
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
