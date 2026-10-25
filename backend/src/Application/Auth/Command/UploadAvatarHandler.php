<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\UserResponse;
use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class UploadAvatarHandler
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly FileStorageInterface $storage,
    ) {
    }

    public function handle(UploadAvatarCommand $cmd): UserResponse
    {
        $ext = strtolower(ltrim($cmd->extension, '.'));
        if (!\in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Invalid file type. Allowed: jpg, jpeg, png, gif, webp');
        }

        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $filename = $cmd->userId.'.'.$ext;
        $this->storage->store('avatars', $filename, $cmd->tmpPath);

        $user->setAvatarUrl('/media/avatars/'.$filename);
        $user->setUpdatedAt(new DateTimeImmutable());

        return UserResponse::from($this->users->save($user));
    }
}
