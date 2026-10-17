<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Notification;

use App\Domain\Notification\Entity\Notification;
use App\Tests\Support\DatabaseWebTestCase;

final class NotificationControllerTest extends DatabaseWebTestCase
{
    private function persistNotification(?string $userId, string $title = 'Title'): Notification
    {
        $n = new Notification();
        $n->setTitle($title);
        $n->setMessage('Message');
        $n->setUserId($userId);
        $this->em->persist($n);
        $this->em->flush();

        return $n;
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/notifications/');

        self::assertResponseStatusCodeSame(401);
    }

    public function testListsOwnAndBroadcastNotifications(): void
    {
        $user = $this->createUser('user@test.com');
        $token = $this->loginAs('user@test.com');

        $this->persistNotification($user->getId(), 'Personal');
        $this->persistNotification(null, 'Broadcast');
        $this->persistNotification('someone-else', 'Not mine');

        $this->client->request('GET', '/api/notifications/', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        $titles = array_column($this->decodeResponse()['notifications'], 'title');
        self::assertContains('Personal', $titles);
        self::assertContains('Broadcast', $titles);
        self::assertNotContains('Not mine', $titles);
    }

    public function testMarksNotificationAsRead(): void
    {
        $user = $this->createUser('user@test.com');
        $token = $this->loginAs('user@test.com');
        $notification = $this->persistNotification($user->getId());

        $this->client->request('PATCH', '/api/notifications/'.$notification->getId().'/read', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertNotNull($this->decodeResponse()['readAt']);
    }

    public function testMarkAllReadReturns204(): void
    {
        $user = $this->createUser('user@test.com');
        $token = $this->loginAs('user@test.com');
        $this->persistNotification($user->getId());

        $this->client->request('PATCH', '/api/notifications/read-all/', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(204);
    }

    public function testDeletesOwnNotification(): void
    {
        $user = $this->createUser('user@test.com');
        $token = $this->loginAs('user@test.com');
        $notification = $this->persistNotification($user->getId());

        $this->client->request('DELETE', '/api/notifications/'.$notification->getId(), [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(204);
    }

    public function testDeleteAllReturns204(): void
    {
        $user = $this->createUser('user@test.com');
        $token = $this->loginAs('user@test.com');
        $this->persistNotification($user->getId());

        $this->client->request('DELETE', '/api/notifications/', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(204);
    }
}
