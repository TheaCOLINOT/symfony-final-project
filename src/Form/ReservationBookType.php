<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * Formulaire de réservation : l'utilisateur choisit la date/heure du rendez-vous.
 */
class ReservationBookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reservationAt', DateTimeType::class, [
                'label' => 'Date et heure du rendez-vous',
                'widget' => 'single_text', // un seul input HTML5 datetime-local
                'input' => 'datetime',
                // Validation : on refuse les dates passées
                'constraints' => [
                    new GreaterThan('now', message: 'Le rendez-vous doit être dans le futur.'),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Continuer vers la validation',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Pas d'entité liée : les données sont traitées étape par étape dans le contrôleur
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
