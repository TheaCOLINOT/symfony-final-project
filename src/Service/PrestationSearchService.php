<?php

namespace App\Service;

use App\Dto\PrestationOffer;
use App\Entity\Cat;
use App\Entity\Location;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Service qui cherche les prestations qu'on peut réserver.
 * Il croise le salon, le type de massage et le masseur chat.
 */
final class PrestationSearchService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Lance la recherche avec les filtres optionnels du formulaire.
     *
     * @return list<PrestationOffer>
     */
    public function search(?Location $location, ?Service $service, ?string $query): array
    {
        $normalizedQuery = $query !== null ? mb_strtolower(trim($query)) : '';

        // Requête de base : chats rattachés à un salon ET qui proposent au moins un service
        $qb = $this->entityManager->createQueryBuilder()
            ->select('c', 'l', 's')
            ->from(Cat::class, 'c')
            ->innerJoin('c.locations', 'l')
            ->innerJoin('c.services', 's')
            ->andWhere('l.isGlobal = false')
            ->orderBy('l.city', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->addOrderBy('c.speciality', 'ASC');

        if ($location !== null) {
            $qb->andWhere('l = :location')
                ->setParameter('location', $location);
        }

        if ($service !== null) {
            $qb->andWhere('s = :service')
                ->setParameter('service', $service);
        }

        if ($normalizedQuery !== '') {
            $this->applyTextFilter($qb, $normalizedQuery);
        }

        /** @var list<Cat> $cats */
        $cats = $qb->getQuery()->getResult();

        // On fabrique la liste finale en PHP (plus simple qu'un SELECT multiple entités)
        $offers = [];
        $seen = [];

        foreach ($cats as $cat) {
            foreach ($cat->getLocations() as $salon) {
                if ($salon->isGlobal()) {
                    continue;
                }

                if ($location !== null && $salon->getId() !== $location->getId()) {
                    continue;
                }

                foreach ($cat->getServices() as $prestation) {
                    if ($service !== null && $prestation->getId() !== $service->getId()) {
                        continue;
                    }

                    if ($normalizedQuery !== '' && !$this->matchesText($prestation, $salon, $cat, $normalizedQuery)) {
                        continue;
                    }

                    // Évite d'avoir deux fois la même combinaison
                    $key = sprintf('%d-%d-%d', $prestation->getId(), $salon->getId(), $cat->getId());
                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $offers[] = new PrestationOffer($prestation, $salon, $cat);
                }
            }
        }

        return $offers;
    }

    /** Filtre SQL pour la barre de recherche texte */
    private function applyTextFilter(QueryBuilder $qb, string $normalizedQuery): void
    {
        $qb->andWhere(
            $qb->expr()->orX(
                'LOWER(s.title) LIKE :query',
                'LOWER(s.description) LIKE :query',
                'LOWER(l.city) LIKE :query',
                'LOWER(l.address) LIKE :query',
                'LOWER(l.country) LIKE :query',
                'LOWER(c.speciality) LIKE :query',
                'LOWER(c.specie) LIKE :query',
                'LOWER(c.color) LIKE :query',
            )
        )->setParameter('query', '%'.$normalizedQuery.'%');
    }

    /** Vérifie en PHP si le texte correspond (au cas où) */
    private function matchesText(Service $service, Location $location, Cat $cat, string $normalizedQuery): bool
    {
        $haystacks = [
            $service->getTitle(),
            $service->getDescription(),
            $location->getCity(),
            $location->getAddress(),
            $location->getCountry(),
            $cat->getSpeciality(),
            $cat->getSpecie(),
            $cat->getColor(),
        ];

        foreach ($haystacks as $value) {
            if ($value !== null && str_contains(mb_strtolower($value), $normalizedQuery)) {
                return true;
            }
        }

        return false;
    }
}
