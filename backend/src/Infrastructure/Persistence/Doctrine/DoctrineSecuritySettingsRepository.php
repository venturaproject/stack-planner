<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Settings\Query\SecuritySettingsReadModelInterface;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecuritySettings>
 */
class DoctrineSecuritySettingsRepository extends ServiceEntityRepository implements SecuritySettingsRepositoryInterface, SecuritySettingsReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecuritySettings::class);
    }

    public function findSingleton(): ?SecuritySettings
    {
        return $this->find(SecuritySettings::SINGLETON_ID);
    }

    public function save(SecuritySettings $settings): SecuritySettings
    {
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();

        return $settings;
    }
}
