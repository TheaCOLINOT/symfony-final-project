<?php
namespace App\Form;
use App\Entity\Cat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
/**
 * Formulaire du profil masseur (entité Cat : espèce, couleur, spécialité).
 */
class CatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('specie', TextType::class, [
                'label' => 'Espèce',
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
            ])
            ->add('speciality', TextType::class, [
                'label' => 'Spécialité massage',
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cat::class,
        ]);
    }
}
