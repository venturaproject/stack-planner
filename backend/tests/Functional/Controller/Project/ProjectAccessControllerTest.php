<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Project;

use App\Domain\Issue\Entity\Issue;
use App\Domain\Planning\Entity\Epic;
use App\Domain\Planning\Entity\Sprint;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Entity\ProjectMember;
use App\Tests\Support\DatabaseWebTestCase;
use DateTimeImmutable;

final class ProjectAccessControllerTest extends DatabaseWebTestCase
{
    public function testMembersAreScopedToTheirProjectsAndAdminsCanSeeAllProjects(): void
    {
        $memberA = $this->createUser('member-a@test.com');
        $memberB = $this->createUser('member-b@test.com');
        $projectA = $this->createProjectWithIssue($memberA->requireId(), 'A');
        $projectB = $this->createProjectWithIssue($memberB->requireId(), 'B');
        $memberAToken = $this->loginAs($memberA->getEmail());

        $this->client->request('GET', '/api/projects', [], [], $this->authHeaders($memberAToken));
        self::assertResponseIsSuccessful();
        self::assertSame([$projectA->getId()], array_column($this->decodeResponse()['data'], 'id'));

        $this->client->request('GET', '/api/issues', [], [], $this->authHeaders($memberAToken));
        self::assertResponseIsSuccessful();
        self::assertSame([$projectA->getId()], array_column($this->decodeResponse()['data'], 'projectId'));

        foreach (['/api/epics', '/api/sprints'] as $planningPath) {
            $this->client->request('GET', $planningPath, [], [], $this->authHeaders($memberAToken));
            self::assertResponseIsSuccessful();
            self::assertSame([$projectA->getId()], array_column($this->decodeResponse()['data'], 'projectId'));
        }

        $issueB = $this->em->getRepository(Issue::class)->findOneBy(['projectId' => $projectB->getId()]);
        self::assertInstanceOf(Issue::class, $issueB);
        $this->client->request('GET', \sprintf('/api/issues/%d', $issueB->getId()), [], [], $this->authHeaders($memberAToken));
        self::assertResponseStatusCodeSame(404);

        $this->client->request(
            'POST',
            '/api/issues',
            [],
            [],
            $this->authHeaders($memberAToken) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['projectId' => $projectB->getId(), 'title' => 'Cross-project issue']),
        );
        self::assertResponseStatusCodeSame(404);

        $admin = $this->createAdmin('global-admin@test.com');
        $adminToken = $this->loginAs($admin->getEmail());
        $this->client->request('GET', '/api/projects', [], [], $this->authHeaders($adminToken));
        self::assertResponseIsSuccessful();
        self::assertEqualsCanonicalizing(
            [$projectA->getId(), $projectB->getId()],
            array_column($this->decodeResponse()['data'], 'id'),
        );

        $this->client->request('GET', '/api/issues', [], [], $this->authHeaders($adminToken));
        self::assertEqualsCanonicalizing(
            [$projectA->getId(), $projectB->getId()],
            array_column($this->decodeResponse()['data'], 'projectId'),
        );
    }

    public function testCreatedProjectAutomaticallyBelongsToItsCreator(): void
    {
        $user = $this->createUser('creator@test.com');
        $token = $this->loginAs($user->getEmail());

        $this->client->request(
            'POST',
            '/api/projects',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'My project', 'key' => 'MINE', 'description' => 'Private to its members']),
        );

        self::assertResponseStatusCodeSame(201);
        $createdId = $this->decodeResponse()['id'];
        $this->client->request('GET', '/api/projects', [], [], $this->authHeaders($token));

        self::assertContains($createdId, array_column($this->decodeResponse()['data'], 'id'));
    }

    public function testAdministratorCanAssignAndRemoveProjectMembers(): void
    {
        $owner = $this->createAdmin('project-admin@test.com');
        $member = $this->createUser('invited@test.com');
        $project = $this->createProjectWithIssue($owner->requireId(), 'OWN');
        $adminToken = $this->loginAs($owner->getEmail());

        $this->client->request(
            'POST',
            \sprintf('/api/projects/%d/members', $project->getId()),
            [],
            [],
            $this->authHeaders($adminToken) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['userId' => $member->requireId(), 'role' => 'member']),
        );
        self::assertResponseStatusCodeSame(201);

        $memberToken = $this->loginAs($member->getEmail());
        $this->client->request('GET', '/api/projects', [], [], $this->authHeaders($memberToken));
        self::assertContains($project->getId(), array_column($this->decodeResponse()['data'], 'id'));

        $this->client->request(
            'DELETE',
            \sprintf('/api/projects/%d/members/%s', $project->getId(), $member->requireId()),
            [],
            [],
            $this->authHeaders($adminToken),
        );
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/projects', [], [], $this->authHeaders($memberToken));
        self::assertSame([], $this->decodeResponse()['data']);
    }

    private function createProjectWithIssue(string $memberId, string $issueSuffix): Project
    {
        $projectKey = 'P'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $project = Project::create('Project '.$issueSuffix, $projectKey, 'Test project', '/demo/project-nova.svg');
        $this->em->persist($project);
        $this->em->flush();
        $this->em->persist(new ProjectMember((int) $project->getId(), $memberId, 'member'));
        $this->em->persist(new Issue((int) $project->getId(), $projectKey.'-'.$issueSuffix, 'Issue '.$issueSuffix, 'todo', 'medium'));
        $this->em->persist(new Epic((int) $project->getId(), $projectKey.'-E1', 'Epic '.$issueSuffix, '', 'open', 0));
        $this->em->persist(new Sprint((int) $project->getId(), 'Sprint '.$issueSuffix, '', 'planning', new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-14')));
        $this->em->flush();

        return $project;
    }
}
