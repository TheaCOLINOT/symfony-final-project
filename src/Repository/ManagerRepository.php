<?php



namespace App\Repository;



use App\Entity\Manager;

use App\Entity\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Doctrine\Persistence\ManagerRegistry;



/**

 * Repository Doctrine pour les profils manager (entité Manager).

 *

 * @extends ServiceEntityRepository<Manager>

 */

class ManagerRepository extends ServiceEntityRepository

{

    public function __construct(ManagerRegistry $registry)

    {

        parent::__construct($registry, Manager::class);

    }



    /**

     * Trouve le profil manager lié à un compte utilisateur.

     */

    public function findOneByUser(User $user): ?Manager

    {

        return $this->findOneBy(['user' => $user]);

    }



    /**

     * Managers disponibles pour être assignés à un salon (pas encore rattachés).

     *

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


