<?php
namespace App\Repository;
use App\Entity\Location;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
/**
 * Repository Doctrine pour les comptes utilisateurs.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Liste tous les utilisateurs triés par nom puis prénom.
     *
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
     * Utilisateurs ayant un rôle précis (ex : ROLE_MANAGER).
     *
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
    /**
     * Compte le nombre d'utilisateurs pour un rôle donné (tableau de bord admin).
     */
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
     * Managers sans profil manager ou sans salon assigné (disponibles pour affectation).
     *
     * @return list<User>
     */
    public function findManagersWithoutLocation(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('App\Entity\Manager', 'm', 'WITH', 'm.user = u')
            ->andWhere('u.role = :role')
            // Pas de ligne manager OU manager sans location
            ->andWhere('m.id IS NULL OR m.location IS NULL')
            ->setParameter('role', UserRole::Manager->value)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    /**
     * Comptes masseur chat sans fiche Cat créée (profil incomplet).
     *
     * @return list<User>
     */
    public function findCatUsersWithoutProfile(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('App\Entity\Cat', 'c', 'WITH', 'c.user = u')
            ->andWhere('u.role = :role')
            ->andWhere('c.id IS NULL')
            ->setParameter('role', UserRole::Cat->value)
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }
    /**
     * Comptes masseur chat inscrits, pas encore rattachés au salon donné.
     *
     * @return list<User>
     */
    public function findCatMasseursNotInLocation(Location $location): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('App\Entity\Cat', 'c', 'WITH', 'c.user = u')
            // Jointure pour détecter si le chat est déjà dans ce salon
            ->leftJoin('c.locations', 'assigned', 'WITH', 'assigned = :location')
            ->andWhere('u.role = :role')
            ->andWhere('assigned.id IS NULL')
            ->setParameter('role', UserRole::Cat->value)
            ->setParameter('location', $location)
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
