<?php

namespace App\Form;

use App\Entity\Participant;
use http\Client\Curl\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class EditParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['role'][0] != 'ROLE_ADMIN') {
            $builder
                ->add('nom', TextType::class, ['attr' => ['readonly' => 'readonly']])
                ->add('prenom', TextType::class, ['attr' => ['readonly' => 'readonly']]);
        } else {
            $builder
                ->add('nom', TextType::class)
                ->add('prenom', TextType::class);
        }
        $builder
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    /*
                    new NotBlank([
                        'message' => 'Veuiller entrer un mot de passe',
                    ]),
                    */
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe est trop court (min 6 characters)',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'required' => false
            ])
            ->add('pseudo', TextType::class)
            ->add('telephone')
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpg',
                            'image/jpeg',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Format incorrect',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'role' => null,
        ]);
    }
}
