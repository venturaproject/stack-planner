<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Issue;

use App\Application\Issue\Command\UploadIssueImageCommand;
use App\Application\Issue\DTO\IssueAttachmentDownloadResponse;
use App\Application\Issue\Query\GetIssueAttachmentQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\Storage\FileStorageInterface;
use App\Infrastructure\Http\UploadedImageValidator;
use App\Infrastructure\Security\ProjectAccessPolicy;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/issues/{issueId}')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class IssueAttachmentController extends AbstractController
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly UploadedImageValidator $uploadedImageValidator,
        private readonly ProjectAccessPolicy $projectAccess,
        private readonly FileStorageInterface $storage,
    ) {
    }

    #[Route('/attachments', methods: ['POST'])]
    public function upload(int $issueId, Request $request): JsonResponse
    {
        $this->projectAccess->assertIssueAccess($issueId);
        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return $this->json(['message' => 'No image uploaded.'], Response::HTTP_BAD_REQUEST);
        }

        $extension = $this->uploadedImageValidator->extension($file);
        if (null === $extension) {
            return $this->json(['message' => 'Image must be JPEG, PNG, GIF or WebP and at most 8 MB.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $mimeType = $file->getMimeType();
        if (null === $mimeType || !\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return $this->json(['message' => 'Image must be JPEG, PNG, GIF or WebP and at most 8 MB.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getUser();
        $authorName = $user instanceof SecurityUser ? $user->domainUser()->getFirstName().' '.$user->domainUser()->getLastName() : 'Planner user';

        return $this->json($this->commandBus->dispatch(new UploadIssueImageCommand(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $extension,
            $mimeType,
            (int) $file->getSize(),
            $issueId,
            $authorName,
        )), Response::HTTP_CREATED);
    }

    #[Route('/attachments/{attachmentId}', methods: ['GET'])]
    public function download(int $issueId, int $attachmentId): Response
    {
        $issue = $this->projectAccess->assertIssueAccess($issueId);
        /** @var IssueAttachmentDownloadResponse|null $attachment */
        $attachment = $this->queryBus->ask(new GetIssueAttachmentQuery($attachmentId));

        if (null === $attachment || $attachment->issueId !== $issueId) {
            throw new NotFoundHttpException();
        }

        $mimeType = $attachment->mimeType;
        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new NotFoundHttpException();
        }

        $contents = $this->storage->read('issue-attachments/'.$issue->getIssueKey(), $attachment->filename);
        if (null === $contents) {
            throw new NotFoundHttpException();
        }

        return new Response($contents, Response::HTTP_OK, [
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => HeaderUtils::makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $attachment->filename),
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    #[Route(
        '/legacy-uploads/{filename}',
        methods: ['GET'],
        requirements: ['filename' => '[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp)'],
    )]
    public function downloadLegacyUpload(int $issueId, string $filename): Response
    {
        $issue = $this->projectAccess->assertIssueAccess($issueId);

        $privateUrl = \sprintf('/api/issues/%d/legacy-uploads/%s', $issueId, $filename);
        $legacyUrl = '/media/issue-uploads/'.$filename;
        $description = $issue->getDescription() ?? '';
        if (!str_contains($description, $privateUrl) && !str_contains($description, $legacyUrl)) {
            throw new NotFoundHttpException();
        }

        $contents = $this->storage->read('issue-uploads', $filename);
        if (null === $contents) {
            throw new NotFoundHttpException();
        }

        $mimeType = match (strtolower(pathinfo($filename, \PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => throw new NotFoundHttpException(),
        };

        return new Response($contents, Response::HTTP_OK, [
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => HeaderUtils::makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename),
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
