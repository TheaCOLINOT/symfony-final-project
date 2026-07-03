<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\CatRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatAssignType extends AbstractType
{
    public function __construct(
        private readonly CatRepository $catRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('masseurUser', EntityType::class, [
            'class' => User::class,
            'choices' => $options['masseurs'],
            'choice_label' => function (User $user): string {
                $cat = $this->catRepository->findOneByUser($user);
                $identity = sprintf(
                    '%s %s (%s)',
                    $user->getFirstname() ?? '',
                    $user->getName() ?? '',
                    $user->getEmail() ?? ''
                );

                if ($cat === null) {
                    return $identity.' — profil masseur à compléter';
                }

                return sprintf(
                    '%s — %s (%s)',
                    $identity,
                    $cat->getSpeciality() ?? 'sans spécialité',
                    $cat->getSpecie() ?? 'chat'
                );
            },
            'label' => 'Masseur chat inscrit',
            'placeholder' => 'Sélectionner un masseur',
            'mapped' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'masseurs' => [],
        ]);

        $resolver->setAllowedTypes('masseurs', 'array');
    }
}
