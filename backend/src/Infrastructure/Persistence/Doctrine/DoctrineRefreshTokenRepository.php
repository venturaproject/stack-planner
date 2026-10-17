<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class DoctrineRefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findByToken(string $token): ?RefreshToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function save(RefreshToken $token): RefreshToken
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();

        return $token;
    }

    public function revokeAllForUser(User $user): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revoked', ':revoked')
            ->where('rt.user = :user')
            ->setParameter('revoked', true, \Doctrine\DBAL\Types\Types::BOOLEAN)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
