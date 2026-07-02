<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    /**
     * @return list<Service>
     */
    public function findGlobalServices(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isGlobal = true')
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Service>
     */
    public function findAllOrderedByTitle(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
