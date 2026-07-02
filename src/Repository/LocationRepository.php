<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findGlobalLocation(): ?Location
    {
        return $this->findOneBy(['isGlobal' => true]);
    }

    /**
     * @return list<Location>
     */
    public function findCityLocations(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.isGlobal = false')
            ->orderBy('l.city', 'ASC')
            ->addOrderBy('l.address', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Location>
     */
    public function findAllOrderedByCity(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.managers', 'm')
            ->addSelect('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->orderBy('l.isGlobal', 'DESC')
            ->addOrderBy('l.city', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
