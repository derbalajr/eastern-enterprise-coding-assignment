<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * Find a country by UUID.
     */
    public function findByUuid(string $uuid): ?Country
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Find all countries ordered by name.
     *
     * @return Country[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all UUIDs currently in the database.
     *
     * @return string[]
     */
    public function findAllUuids(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.uuid')
            ->getQuery()
            ->getResult();

        return array_column($result, 'uuid');
    }

    /**
     * Remove countries that are not in the provided UUID list.
     *
     * @param string[] $validUuids
     * @return int Number of countries removed
     */
    public function removeCountriesNotInList(array $validUuids): int
    {
        if (empty($validUuids)) {
            return 0;
        }

        $qb = $this->createQueryBuilder('c');
        $qb->delete()
            ->where($qb->expr()->notIn('c.uuid', ':uuids'))
            ->setParameter('uuids', $validUuids);

        return $qb->getQuery()->execute();
    }
}
