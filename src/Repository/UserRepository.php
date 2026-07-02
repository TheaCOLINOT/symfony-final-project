<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return list<User>
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<User>
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByRole(UserRole $role): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<User>
     */
    public function findManagersWithoutLocation(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('App\Entity\Manager', 'm', 'WITH', 'm.user = u')
            ->andWhere('u.role = :role')
            ->andWhere('m.id IS NULL OR m.location IS NULL')
            ->setParameter('role', UserRole::Manager->value)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}