<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

use App\Domain\Issue\Entity\Issue;
use App\Domain\Issue\Entity\IssueActivity;
use App\Domain\Issue\Entity\IssueAttachment;
use App\Domain\Issue\Entity\IssueChecklistItem;
use App\Domain\Issue\Entity\IssueComment;
use App\Domain\Issue\Entity\IssueLink;
use App\Domain\Issue\Entity\IssueWorklog;

interface IssueReadModelInterface
{
    /**
     * @param int[]|null $visibleProjectIds null allows all projects; an empty list allows none
     *
     * @return Issue[]
     */
    public function findForBoard(?int $projectId, ?array $visibleProjectIds = null): array;

    public function findById(int $id): ?Issue;

    public function findAttachmentById(int $id): ?IssueAttachment;

    /** @return IssueComment[] */
    public function findComments(int $issueId): array;

    /** @return IssueChecklistItem[] */
    public function findChecklist(int $issueId): array;

    /** @return IssueWorklog[] */
    public function findWorklogs(int $issueId): array;

    /** @return IssueLink[] */
    public function findLinks(int $issueId): array;

    /** @return IssueActivity[] */
    public function findActivities(int $issueId): array;

    /** @return IssueAttachment[] */
    public function findAttachments(int $issueId): array;
}
