<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Issue;

use App\Application\Issue\Command\UploadIssueImageCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\Http\UploadedImageValidator;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/uploads/image')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class IssueImageUploadController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly UploadedImageValidator $uploadedImageValidator,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return $this->json(['message' => 'No image uploaded.'], Response::HTTP_BAD_REQUEST);
        }
        $extension = $this->uploadedImageValidator->extension($file);
        if (null === $extension) {
            return $this->json(['message' => 'Image must be JPEG, PNG, GIF or WebP and at most 8 MB.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->getUser();
        $authorName = $user instanceof SecurityUser ? $user->domainUser()->getFirstName().' '.$user->domainUser()->getLastName() : 'Planner user';
        $issueId = $request->request->getInt('issue_id') ?: null;

        return $this->json($this->commandBus->dispatch(new UploadIssueImageCommand(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $extension,
            $file->getMimeType(),
            (int) $file->getSize(),
            $issueId,
            $authorName,
        )), Response::HTTP_CREATED);
    }
}
