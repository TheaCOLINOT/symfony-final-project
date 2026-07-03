<?php

namespace App\Repository;

use App\Entity\Cat;
use App\Entity\Location;
use App\Entity\User;
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
            ->innerJoin('c.locations', 'l')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->andWhere('l = :location')
            ->setParameter('location', $location)
            ->orderBy('c.speciality', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUser(User $user): ?Cat
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * @return list<Cat>
     */
    public function findAllOrderedBySpeciality(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.locations', 'l')
            ->addSelect('l')
            ->orderBy('c.speciality', 'ASC')
            ->addOrderBy('c.specie', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
