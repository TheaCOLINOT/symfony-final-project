<?php



namespace App\Form;



use App\Entity\Location;

use App\Entity\User;

use App\Enum\UserRole;

use App\Repository\UserRepository;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;



/**

 * Formulaire admin : créer une ville/salon et y rattacher un manager.

 */

class AdminLocationType extends AbstractType

{

    public function __construct(

        private readonly UserRepository $userRepository,

    ) {

    }



    public function buildForm(FormBuilderInterface $builder, array $options): void

    {

        $builder

            ->add('address', TextType::class, [

                'label' => 'Adresse du salon',

            ])

            ->add('city', TextType::class, [

                'label' => 'Ville',

            ])

            ->add('country', TextType::class, [

                'label' => 'Pays',

            ])

            ->add('managerUser', EntityType::class, [

                'class' => User::class,

                // Seuls les managers sans salon assigné peuvent être choisis

                'choices' => $this->userRepository->findManagersWithoutLocation(),

                'choice_label' => static fn (User $user) => sprintf(

                    '%s %s (%s)',

                    $user->getFirstname(),

                    $user->getName(),

                    $user->getEmail()

                ),

                'label' => 'Manager de la ville',

                'placeholder' => 'Sélectionner un manager',

                // Le manager est géré à part dans le contrôleur (relation Manager ↔ Location)

                'mapped' => false,

            ]);

    }



    public function configureOptions(OptionsResolver $resolver): void

    {

        $resolver->setDefaults([

            'data_class' => Location::class,

        ]);

    }

}

