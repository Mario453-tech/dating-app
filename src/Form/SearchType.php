<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('seeking', ChoiceType::class, [
                'choices' => [
                    'Szukam...' => '',
                    'Kobiety' => 'female',
                    'Mężczyzny' => 'male',
                ],
                'required' => true,
            ])
            ->add('age_from', IntegerType::class, [
                'required' => false,
            ])
            ->add('age_to', IntegerType::class, [
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
