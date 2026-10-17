<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\ApiClient\Query\ApiClientReadModelInterface;
use App\Domain\ApiClient\Entity\ApiClient;
use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ApiClient> */
final class DoctrineApiClientRepository extends ServiceEntityRepository implements ApiClientRepositoryInterface, ApiClientReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClient::class);
    }

    public function findById(string $id): ?ApiClient
    {
        return $this->find($id);
    }

    public function findByClientId(string $clientId): ?ApiClient
    {
        return $this->findOneBy(['clientId' => $clientId]);
    }

    /** @return ApiClient[] */
    /** @return list<ApiClient> */
    public function findAllClients(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(ApiClient $client): ApiClient
    {
        $this->getEntityManager()->persist($client);
        $this->getEntityManager()->flush();

        return $client;
    }

    public function updateLastUsed(string $clientId): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.lastUsedAt', ':now')
            ->where('c.clientId = :clientId')
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->execute();
    }
}
