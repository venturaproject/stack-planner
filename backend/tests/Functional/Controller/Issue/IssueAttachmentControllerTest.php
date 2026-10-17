<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Issue;

use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Issue\Entity\Issue;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Entity\ProjectMember;
use App\Tests\Support\DatabaseWebTestCase;
use App\Tests\Support\TestImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class IssueAttachmentControllerTest extends DatabaseWebTestCase
{
    public function testUploadReturnsAContextBoundAuthenticatedUrlAndDownloadsPrivately(): void
    {
        $user = $this->createUser('attachment@test.com');
        $token = $this->loginAs($user->getEmail());
        $issue = $this->createIssue($user->requireId());

        $path = tempnam(sys_get_temp_dir(), 'issue-image-');
        self::assertNotFalse($path);
        file_put_contents($path, TestImage::onePixelPng());
        $file = new UploadedFile($path, 'proof.png', null, null, true);

        $this->client->request(
            'POST',
            \sprintf('/api/issues/%d/attachments', $issue->getId()),
            [],
            ['file' => $file],
            $this->authHeaders($token),
        );

        self::assertResponseStatusCodeSame(201);
        $uploaded = $this->decodeResponse();
        $expectedUrl = \sprintf('/api/issues/%d/attachments/%d', $issue->getId(), $uploaded['attachment']['id']);
        self::assertSame($expectedUrl, $uploaded['url']);

        $this->client->request('GET', $uploaded['url'], [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertSame('image/png', $this->client->getResponse()->headers->get('Content-Type'));
        $cacheControl = $this->client->getResponse()->headers->get('Cache-Control');
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('no-store', $cacheControl);
        self::assertSame(TestImage::onePixelPng(), $this->client->getResponse()->getContent());

        @unlink($path);
    }

    public function testAttachmentCannotBeReadThroughAnotherIssueContext(): void
    {
        $user = $this->createUser('context@test.com');
        $token = $this->loginAs($user->getEmail());
        $issue = $this->createIssue($user->requireId(), 'SEC-1');
        $otherIssue = $this->createIssue($user->requireId(), 'SEC-2');
        $attachmentId = $this->storeAttachment($issue, $token);

        $this->client->request(
            'GET',
            \sprintf('/api/issues/%d/attachments/%d', $otherIssue->getId(), $attachmentId),
            [],
            [],
            $this->authHeaders($token),
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testAttachmentRequiresAuthentication(): void
    {
        $user = $this->createUser('unauthenticated-attachment@test.com');
        $issue = $this->createIssue($user->requireId());
        $this->client->request('GET', \sprintf('/api/issues/%d/attachments/1', $issue->getId()));

        self::assertResponseStatusCodeSame(401);
    }

    public function testLegacyUnattachedImageIsScopedToTheIssueThatReferencesIt(): void
    {
        $user = $this->createUser('legacy-attachment@test.com');
        $token = $this->loginAs($user->getEmail());
        $filename = bin2hex(random_bytes(20)).'.png';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'legacy-issue-image-');
        self::assertNotFalse($temporaryPath);
        file_put_contents($temporaryPath, TestImage::onePixelPng());
        self::getContainer()->get(FileStorageInterface::class)->store('issue-uploads', $filename, $temporaryPath);
        $issue = $this->createIssue($user->requireId(), 'LEG-1', '<p><img src="/media/issue-uploads/'.$filename.'"></p>');
        $privateUrl = \sprintf('/api/issues/%d/legacy-uploads/%s', $issue->getId(), $filename);

        $this->client->request('GET', \sprintf('/api/issues/%d', $issue->getId()), [], [], $this->authHeaders($token));
        self::assertStringContainsString($privateUrl, $this->decodeResponse()['description']);

        $this->client->request('GET', $privateUrl, [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame(TestImage::onePixelPng(), $this->client->getResponse()->getContent());

        $otherIssue = $this->createIssue($user->requireId(), 'LEG-2');
        $this->client->request(
            'GET',
            \sprintf('/api/issues/%d/legacy-uploads/%s', $otherIssue->getId(), $filename),
            [],
            [],
            $this->authHeaders($token),
        );
        self::assertResponseStatusCodeSame(404);
    }

    private function createIssue(string $memberId, string $key = 'SEC-1', ?string $description = null): Issue
    {
        $projectKey = 'SEC'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $project = Project::create('Security test', $projectKey, 'Test project', '/demo/project-nova.svg');
        $this->em->persist($project);
        $this->em->flush();
        $this->em->persist(new ProjectMember((int) $project->getId(), $memberId, 'member'));

        $issue = new Issue((int) $project->getId(), $key, 'Private attachment', 'todo', 'medium', description: $description);
        $this->em->persist($issue);
        $this->em->flush();

        return $issue;
    }

    private function storeAttachment(Issue $issue, string $token): int
    {
        $path = tempnam(sys_get_temp_dir(), 'issue-image-');
        self::assertNotFalse($path);
        file_put_contents($path, TestImage::onePixelPng());
        $file = new UploadedFile($path, 'proof.png', null, null, true);
        $this->client->request(
            'POST',
            \sprintf('/api/issues/%d/attachments', $issue->getId()),
            [],
            ['file' => $file],
            $this->authHeaders($token),
        );
        @unlink($path);
        self::assertResponseStatusCodeSame(201);

        return (int) $this->decodeResponse()['attachment']['id'];
    }
}
