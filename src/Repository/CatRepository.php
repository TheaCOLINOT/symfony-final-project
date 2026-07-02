<?php

namespace App\Repository;

use App\Entity\Cat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cat>
 */
class CatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cat::class);
    }

    /**
     * @return list<Cat>
     */
    public function findByLocation(Location $location): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.location = :location')
            ->setParameter('location', $location)
            ->orderBy('c.speciality', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Cat>
     */
    public function findAllOrderedBySpeciality(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.speciality', 'ASC')
            ->addOrderBy('c.specie', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
