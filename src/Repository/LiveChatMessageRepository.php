<?php

namespace App\Repository;

use App\Entity\LiveChatMessage;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LiveChatMessage>
 */
class LiveChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveChatMessage::class);
    }

    /**
     * @return list<LiveChatMessage>
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
