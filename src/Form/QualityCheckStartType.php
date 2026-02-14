<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QualityCheckStartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shiftManager', TextType::class , [
            'label' => 'Shiftmanager',
            'required' => true,
            'attr' => ['class' => 'w-full rounded-xl border-gray-300 shadow-sm focus:border-yellow-400 focus:ring-yellow-400'],
        ])
            ->add('crew', TextType::class , [
            'label' => 'Crewlid',
            'required' => true,
            'attr' => ['class' => 'w-full rounded-xl border-gray-300 shadow-sm focus:border-yellow-400 focus:ring-yellow-400'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}