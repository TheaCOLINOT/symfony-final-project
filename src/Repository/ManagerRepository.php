<?php

namespace App\Repository;

use App\Entity\Manager;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Manager>
 */
class ManagerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manager::class);
    }

    public function findOneByUser(User $user): ?Manager
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * @return list<Manager>
     */
    public function findAvailableForLocationAssignment(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.location IS NULL')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
