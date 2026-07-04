<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\Service;
use App\Repository\LocationRepository;
use App\Repository\ServiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de recherche de prestations (filtres centre, type, mot-clé).
 */
class PrestationSearchType extends AbstractType
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly ServiceRepository $serviceRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choices' => $this->locationRepository->findCityLocations(),
                'choice_label' => static fn (Location $location) => sprintf(
                    '%s — %s',
                    $location->getCity(),
                    $location->getAddress()
                ),
                'label' => 'Centre / salon',
                'placeholder' => 'Tous les centres',
                'required' => false,
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choices' => $this->serviceRepository->findGlobalServices(),
                'choice_label' => static fn (Service $service) => sprintf(
                    '%s (%s€ · %s)',
                    $service->getTitle(),
                    $service->getPrice(),
                    $service->getDuration()
                ),
                'label' => 'Type de prestation',
                'placeholder' => 'Toutes les prestations',
                'required' => false,
            ])
            ->add('q', SearchType::class, [
                'label' => 'Recherche précise',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Mot-clé, ville, spécialité du masseur…',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Rechercher',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'method' => 'GET',           // les filtres passent dans l'URL (partageable)
            'csrf_protection' => false,    // pas de token CSRF pour un formulaire GET
        ]);
    }

    /**
     * Préfixe vide pour avoir ?location= au lieu de ?prestation_search[location]=
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
