<?php
namespace App\Repository;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
/**
 * Repository Doctrine pour les salons de massage (entité Location).
 *
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }
    /**
     * Récupère le salon "global" géré par l'administrateur principal.
     */
    public function findGlobalLocation(): ?Location
    {
        return $this->findOneBy(['isGlobal' => true]);
    }
    /**
     * Liste les salons physiques (ceux qui ne sont pas le global).
     *
     * @return list<Location>
     */
    public function findCityLocations(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.isGlobal = false')
            ->andWhere('l.isRemote = false')
            ->orderBy('l.city', 'ASC')
            ->addOrderBy('l.address', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Lieu virtuel pour les prestations à distance. */
    public function findRemoteLocation(): ?Location
    {
        return $this->findOneBy(['isRemote' => true]);
    }
    /**
     * Liste tous les salons avec leurs managers, utilisateurs et chats (pour l'admin).
     *
     * @return list<Location>
     */
    public function findAllOrderedByCity(): array
    {
        return $this->createQueryBuilder('l')
            // On charge les relations utiles en une seule requête
            ->leftJoin('l.managers', 'm')
            ->addSelect('m')
            ->leftJoin('m.user', 'u')
            ->addSelect('u')
            ->leftJoin('l.cats', 'c')
            ->addSelect('c')
            // Le salon global en premier, puis tri par ville
            ->orderBy('l.isGlobal', 'DESC')
            ->addOrderBy('l.city', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
