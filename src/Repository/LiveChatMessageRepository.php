<?php
namespace App\Repository;
use App\Entity\LiveChatMessage;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les messages du live chat.
 *
 * @extends ServiceEntityRepository<LiveChatMessage>
 */
class LiveChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveChatMessage::class);
    }

    /**
     * Tous les messages d'une réservation, du plus ancien au plus récent.
     */
    public function findByReservationOrdered(Reservation $reservation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.reservation = :reservation')
            ->setParameter('reservation', $reservation)
            ->orderBy('m.createdAt', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
