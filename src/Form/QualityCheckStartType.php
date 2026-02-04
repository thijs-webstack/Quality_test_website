<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QualityCheckStartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shiftManager', TextType::class, [
                'label' => 'Shiftmanager',
                'required' => true,
            ])
            ->add('crew', TextType::class, [
                'label' => 'Crewlid',
                'required' => true,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Categorie',
                'choices' => [
                    'Burger' => 'burger',
                    'IJs' => 'ijs',
                    'Friet' => 'friet',
                ],
                'required' => true,
                'placeholder' => 'Kies een categorie',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
