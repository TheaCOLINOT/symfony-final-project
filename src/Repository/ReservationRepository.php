<?php



namespace App\Repository;



use App\Entity\Cat;

use App\Entity\Reservation;

use App\Entity\User;

use App\Enum\ReservationStatus;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

use Doctrine\Persistence\ManagerRegistry;



/**

 * Repository Doctrine pour les réservations de prestations.

 *

 * @extends ServiceEntityRepository<Reservation>

 */

class ReservationRepository extends ServiceEntityRepository

{

    public function __construct(ManagerRegistry $registry)

    {

        parent::__construct($registry, Reservation::class);

    }



    /**

     * Réservations confirmées d'un masseur chat sur une période donnée (planning).

     *

     * @return list<Reservation>

     */

    public function findConfirmedByCatBetween(Cat $cat, \DateTimeInterface $start, \DateTimeInterface $end): array

    {

        return $this->createQueryBuilder('r')

            // La réservation peut concerner plusieurs chats, on filtre sur celui demandé

            ->innerJoin('r.cats', 'c')

            ->addSelect('c')

            ->leftJoin('r.service', 's')

            ->addSelect('s')

            ->leftJoin('r.location', 'l')

            ->addSelect('l')

            ->leftJoin('r.user', 'u')

            ->addSelect('u')

            ->andWhere('c = :cat')

            ->andWhere('r.status = :status')

            // Intervalle semi-ouvert : >= début et < fin

            ->andWhere('r.reservationDate >= :start')

            ->andWhere('r.reservationDate < :end')

            ->setParameter('cat', $cat)

            ->setParameter('status', ReservationStatus::Confirmed->value)

            ->setParameter('start', $start)

            ->setParameter('end', $end)

            ->orderBy('r.reservationDate', 'ASC')

            ->getQuery()

            ->getResult();

    }



    /**

     * Prochaines réservations confirmées d'un client (optionnellement limitées).

     *

     * @return list<Reservation>

     */

    public function findUpcomingByUser(User $user, ?int $limit = null): array

    {

        $qb = $this->createQueryBuilder('r')

            ->leftJoin('r.service', 's')

            ->addSelect('s')

            ->leftJoin('r.location', 'l')

            ->addSelect('l')

            ->leftJoin('r.cats', 'c')

            ->addSelect('c')

            ->andWhere('r.user = :user')

            ->andWhere('r.status = :status')

            ->andWhere('r.reservationDate >= :now')

            ->setParameter('user', $user)

            ->setParameter('status', ReservationStatus::Confirmed->value)

            ->setParameter('now', new \DateTime())

            ->orderBy('r.reservationDate', 'ASC');



        if ($limit !== null) {

            $qb->setMaxResults($limit);

        }



        return $qb->getQuery()->getResult();

    }



    /**

     * Réservations passées et confirmées d'un client (historique).

     *

     * @return list<Reservation>

     */

    public function findPastByUser(User $user): array

    {

        return $this->createQueryBuilder('r')

            ->leftJoin('r.service', 's')

            ->addSelect('s')

            ->leftJoin('r.location', 'l')

            ->addSelect('l')

            ->leftJoin('r.cats', 'c')

            ->addSelect('c')

            ->andWhere('r.user = :user')

            ->andWhere('r.status = :status')

            ->andWhere('r.reservationDate < :now')

            ->setParameter('user', $user)

            ->setParameter('status', ReservationStatus::Confirmed->value)

            ->setParameter('now', new \DateTime())

            ->orderBy('r.reservationDate', 'DESC')

            ->getQuery()

            ->getResult();

    }

}


