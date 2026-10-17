<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Security;

use App\Infrastructure\Http\Security\Require2faEnforcementListener;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class Require2faEnforcementListenerTest extends TestCase
{
    public function testDoesNotTryToDecodeAnApiClientTokenAsAJwt(): void
    {
        $apiClientToken = new UsernamePasswordToken(new InMemoryUser('client', 'password'), 'api_v1', ['ROLE_API_CLIENT']);
        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn($apiClientToken);

        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $jwtManager->expects(self::never())->method('decode');

        $event = new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/api/v1/products'),
            HttpKernelInterface::MAIN_REQUEST,
        );

        (new Require2faEnforcementListener($security, $jwtManager))($event);

        self::assertNull($event->getResponse());
    }
}
