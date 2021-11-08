<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Sortie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('dateDebut')
            ->add('duree')
            ->add('dateCloture')
            ->add('nbInscriptionsMax')
            ->add('descriptionInfos')
            ->add('urlPhoto')
            ->add('lieu', LieuType::class, [
                'class' => 'App\Entity\Lieu',
                'mapped' => false,
                'label' => 'nom',
                'placeholder' => 'Selectionner un lieu',
                'required' => false
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
