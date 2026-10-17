<?php

declare(strict_types=1);

namespace App\Domain\Issue\Repository;

use App\Domain\Issue\Entity\IssueAttachment;

interface IssueAttachmentRepositoryInterface
{
    public function save(IssueAttachment $attachment): void;
}
