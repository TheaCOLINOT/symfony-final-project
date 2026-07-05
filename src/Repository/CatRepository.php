<?php



namespace App\Repository;



use App\Entity\Cat;

use App\Entity\Location;

use App\Entity\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Doctrine\Persistence\ManagerRegistry;



/**

 * Repository Doctrine pour les masseurs chats (entité Cat).

 * On y met les requêtes personnalisées au lieu de tout faire dans les contrôleurs.

 *

 * @extends ServiceEntityRepository<Cat>

 */

class CatRepository extends ServiceEntityRepository

{

    public function __construct(ManagerRegistry $registry)

    {

        parent::__construct($registry, Cat::class);

    }



    /**

     * Récupère les masseurs chats rattachés à un salon donné.

     *

     * @return list<Cat>

     */

    public function findByLocation(Location $location): array

    {

        return $this->createQueryBuilder('c')

            // On ne garde que les chats liés au salon via la table de jointure

            ->innerJoin('c.locations', 'l')

            // On charge aussi le compte utilisateur pour éviter des requêtes en plus

            ->leftJoin('c.user', 'u')

            ->addSelect('u')

            ->andWhere('l = :location')

            ->setParameter('location', $location)

            ->orderBy('c.speciality', 'ASC')

            ->getQuery()

            ->getResult();

    }



    /**

     * Trouve le profil chat associé à un compte utilisateur (connexion masseur).

     */

    public function findOneByUser(User $user): ?Cat

    {

        return $this->findOneBy(['user' => $user]);

    }



    /**

     * Liste tous les masseurs chats, triés par spécialité puis par espèce.

     *

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

    /**
     * Tous les masseurs chats, même ceux sans salon.
     * Utile pour le live chat à distance.
     */
    public function findAllForRemoteLiveChat(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.speciality', 'ASC')
            ->addOrderBy('c.specie', 'ASC')
            ->getQuery()
            ->getResult();
    }

}


