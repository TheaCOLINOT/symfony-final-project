<?php



namespace App\Form;



use App\Entity\Service;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;



/**

 * Formulaire Symfony pour créer ou modifier un service (prestation).

 */

class ServiceType extends AbstractType

{

    public function buildForm(FormBuilderInterface $builder, array $options): void

    {

        // On ajoute les champs correspondant aux propriétés de l'entité Service

        $builder

            ->add('title', TextType::class, [

                'label' => 'Nom du service',

            ])

            ->add('description', TextareaType::class, [

                'label' => 'Description',

            ])

            ->add('duration', TextType::class, [

                'label' => 'Durée',

            ])

            ->add('price', IntegerType::class, [

                'label' => 'Prix',

            ]);

    }



    public function configureOptions(OptionsResolver $resolver): void

    {

        // Le formulaire remplit directement un objet Service

        $resolver->setDefaults([

            'data_class' => Service::class,

        ]);

    }

}

