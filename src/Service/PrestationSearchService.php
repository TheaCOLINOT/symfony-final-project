<?php

namespace App\Service;

use App\Dto\PrestationOffer;
use App\Entity\Cat;
use App\Entity\Location;
use App\Entity\Service;
use App\Repository\CatRepository;
use App\Repository\LocationRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Service qui cherche les prestations qu'on peut réserver.
 * Il croise le salon, le type de massage et le masseur chat.
 * Depuis le live chat, on ajoute aussi les offres à distance.
 */
final class PrestationSearchService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceRepository $serviceRepository,
        private readonly LocationRepository $locationRepository,
        private readonly CatRepository $catRepository,
    ) {
    }

    /**
     * Lance la recherche avec les filtres optionnels du formulaire.
     */
    public function search(?Location $location, ?Service $service, ?string $query): array
    {
        $normalizedQuery = $query !== null ? mb_strtolower(trim($query)) : '';

        // Recherche classique dans les salons
        $offers = $this->searchInSalons($location, $service, $normalizedQuery);

        // + la prestation spéciale live chat (tous les chats, à distance)
        $remoteOffers = $this->searchRemoteLiveChat($location, $service, $normalizedQuery);

        return array_merge($offers, $remoteOffers);
    }

    /** Recherche normale : salon + masseur + prestation cochée par le chat */
    private function searchInSalons(?Location $location, ?Service $service, string $normalizedQuery): array
    {
        // Si on filtre sur un salon physique, pas de live chat dans les résultats
        if ($location !== null && $location->isRemote()) {
            return [];
        }

        // Si on cherche une prestation précise qui n'est pas le live chat
        if ($service !== null && $service->isRemoteLiveChat()) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('c', 'l', 's')
            ->from(Cat::class, 'c')
            ->innerJoin('c.locations', 'l')
            ->innerJoin('c.services', 's')
            ->andWhere('l.isGlobal = false')
            ->andWhere('l.isRemote = false')
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

        $cats = $qb->getQuery()->getResult();

        // On fabrique la liste en PHP pour éviter les doublons
        $offers = [];
        $seen = [];

        foreach ($cats as $cat) {
            foreach ($cat->getLocations() as $salon) {
                if ($salon->isGlobal() || $salon->isRemote()) {
                    continue;
                }

                if ($location !== null && $salon->getId() !== $location->getId()) {
                    continue;
                }

                foreach ($cat->getServices() as $prestation) {
                    if ($prestation->isRemoteLiveChat()) {
                        continue;
                    }

                    if ($service !== null && $prestation->getId() !== $service->getId()) {
                        continue;
                    }

                    if ($normalizedQuery !== '' && !$this->matchesText($prestation, $salon, $cat, $normalizedQuery)) {
                        continue;
                    }

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

    /**
     * Live chat à distance : proposé par TOUS les chats, sans salon.
     * Le masseur n'a même pas besoin de cocher la prestation.
     */
    private function searchRemoteLiveChat(?Location $location, ?Service $service, string $normalizedQuery): array
    {
        // Filtre salon : si l'utilisateur a choisi Paris par ex., pas de live chat
        if ($location !== null && !$location->isRemote()) {
            return [];
        }

        $remoteService = $this->serviceRepository->findRemoteLiveChatService();
        $remoteLocation = $this->locationRepository->findRemoteLocation();

        if ($remoteService === null || $remoteLocation === null) {
            return [];
        }

        if ($service !== null && $service->getId() !== $remoteService->getId()) {
            return [];
        }

        $offers = [];
        $allCats = $this->catRepository->findAllForRemoteLiveChat();

        foreach ($allCats as $cat) {
            if ($normalizedQuery !== '' && !$this->matchesRemoteText($remoteService, $cat, $normalizedQuery)) {
                continue;
            }

            $offers[] = new PrestationOffer($remoteService, $remoteLocation, $cat);
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

    /** Vérifie en PHP si le texte correspond (pour les prestations en salon) */
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

    /** Même principe mais pour le live chat à distance */
    private function matchesRemoteText(Service $service, Cat $cat, string $normalizedQuery): bool
    {
        $haystacks = [
            $service->getTitle(),
            $service->getDescription(),
            Location::REMOTE_CITY,
            'en ligne',
            'live chat',
            'à distance',
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
