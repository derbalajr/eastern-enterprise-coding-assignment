<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * Add condition to exclude soft-deleted countries.
     */
    private function addNotDeletedCondition(QueryBuilder $qb): QueryBuilder
    {
        return $qb->andWhere('c.deletedAt IS NULL');
    }

    /**
     * Find a country by UUID (excluding soft-deleted).
     */
    public function findByUuid(string $uuid): ?Country
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.uuid = :uuid')
            ->setParameter('uuid', $uuid);
        
        return $this->addNotDeletedCondition($qb)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all countries ordered by name (excluding soft-deleted).
     *
     * @param int $page Page number (1-based)
     * @param int $limit Number of items per page
     * @return Country[]
     */
    public function findAllOrderedByName(int $page = 1, int $limit = 50): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        
        return $this->addNotDeletedCondition($qb)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count all countries (excluding soft-deleted).
     */
    public function countAll(): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');
        
        return (int) $this->addNotDeletedCondition($qb)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all UUIDs currently in the database (excluding soft-deleted).
     *
     * @return string[]
     */
    public function findAllUuids(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.uuid');
        
        $result = $this->addNotDeletedCondition($qb)
            ->getQuery()
            ->getResult();

        return array_column($result, 'uuid');
    }

    /**
     * Find a country by UUID including soft-deleted ones.
     */
    public function findByUuidIncludingDeleted(string $uuid): ?Country
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Soft delete countries that are not in the provided UUID list.
     *
     * @param string[] $validUuids
     * @return int Number of countries soft-deleted
     */
    public function removeCountriesNotInList(array $validUuids): int
    {
        if (empty($validUuids)) {
            return 0;
        }

        $qb = $this->createQueryBuilder('c');
        $qb->update()
            ->set('c.deletedAt', ':now')
            ->where($qb->expr()->notIn('c.uuid', ':uuids'))
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('uuids', $validUuids)
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->execute();
    }
}
