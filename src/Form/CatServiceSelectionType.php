<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatServiceSelectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('services', EntityType::class, [
            'class' => Service::class,
            'choices' => $options['services'],
            'choice_label' => static fn (Service $service) => sprintf(
                '%s - %s€ (%s)',
                $service->getTitle(),
                $service->getPrice(),
                $service->getDuration()
            ),
            'multiple' => true,
            'expanded' => true,
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'services' => [],
        ]);

        $resolver->setAllowedTypes('services', 'array');
    }
}
