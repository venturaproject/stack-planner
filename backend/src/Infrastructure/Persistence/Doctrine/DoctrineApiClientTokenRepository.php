<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\ApiClient\Entity\ApiClient;
use App\Domain\ApiClient\Entity\ApiClientToken;
use App\Domain\ApiClient\Repository\ApiClientTokenRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ApiClientToken> */
final class DoctrineApiClientTokenRepository extends ServiceEntityRepository implements ApiClientTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClientToken::class);
    }

    public function findByAccessTokenHash(string $hash): ?ApiClientToken
    {
        return $this->findOneBy(['accessTokenHash' => $hash]);
    }

    public function findByRefreshTokenHash(string $hash): ?ApiClientToken
    {
        return $this->findOneBy(['refreshTokenHash' => $hash]);
    }

    public function save(ApiClientToken $token): ApiClientToken
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();

        return $token;
    }

    public function deleteByApiClient(ApiClient $apiClient): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.apiClient = :apiClient')
            ->setParameter('apiClient', $apiClient)
            ->getQuery()
            ->execute();
    }

    public function deleteByRefreshTokenHash(string $hash): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.refreshTokenHash = :hash')
            ->setParameter('hash', $hash)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.refreshExpiresAt < :now')
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
