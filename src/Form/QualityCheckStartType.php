<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class QualityCheckStartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shiftManager', TextType::class , [
            'label' => 'Shiftmanager',
            'required' => true,
            'constraints' => [
                new NotBlank(message: 'Vul de naam van de shiftmanager in.'),
                new Length(max: 100, maxMessage: 'Naam mag maximaal {{ limit }} tekens zijn.'),
            ],
            'attr' => ['class' => 'w-full rounded-xl border-gray-300 shadow-sm focus:border-yellow-400 focus:ring-yellow-400'],
        ])
            ->add('crew', TextType::class , [
            'label' => 'Crewlid',
            'required' => true,
            'constraints' => [
                new NotBlank(message: 'Vul de naam van het crewlid in.'),
                new Length(max: 100, maxMessage: 'Naam mag maximaal {{ limit }} tekens zijn.'),
            ],
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