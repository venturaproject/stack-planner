<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Settings\Command;

use App\Application\Settings\Command\UpdateSecuritySettingsCommand;
use App\Application\Settings\Command\UpdateSecuritySettingsHandler;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Event\SecuritySettingsUpdated;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class UpdateSecuritySettingsHandlerTest extends TestCase
{
    public function testTogglesTheFlagPersistsItAndRecordsAnEventForTheOutbox(): void
    {
        $settings = new SecuritySettings();
        $settings->setRequire2fa(false);

        $repo = $this->createMock(SecuritySettingsRepositoryInterface::class);
        $repo->method('findSingleton')->willReturn($settings);
        $repo->expects(self::once())->method('save')->with($settings)->willReturnArgument(0);

        $handler = new UpdateSecuritySettingsHandler($repo);

        $result = $handler->handle(new UpdateSecuritySettingsCommand(true));

        self::assertTrue($result->require2fa);
        self::assertTrue($settings->isRequire2fa());
        self::assertInstanceOf(SecuritySettingsUpdated::class, $settings->pullEvents()[0]);
    }
}
