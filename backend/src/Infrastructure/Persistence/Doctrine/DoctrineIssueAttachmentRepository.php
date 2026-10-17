<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Issue\Entity\IssueAttachment;
use App\Domain\Issue\Repository\IssueAttachmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineIssueAttachmentRepository implements IssueAttachmentRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(int $id): ?IssueAttachment
    {
        return $this->entityManager->find(IssueAttachment::class, $id);
    }

    public function save(IssueAttachment $attachment): void
    {
        $this->entityManager->persist($attachment);
        $this->entityManager->flush();
    }
}
