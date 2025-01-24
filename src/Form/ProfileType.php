<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nazwa użytkownika',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Imię',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nazwisko',
                'required' => false,
            ])
            ->add('birthDate', BirthdayType::class, [
                'label' => 'Data urodzenia',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Płeć',
                'choices' => [
                    'Mężczyzna' => 'M',
                    'Kobieta' => 'F',
                    'Inne' => 'O',
                ],
                'required' => false,
            ])
            ->add('seekingGender', ChoiceType::class, [
                'label' => 'Szukam',
                'choices' => [
                    'Mężczyzny' => 'M',
                    'Kobiety' => 'F',
                    'Wszystkich' => 'A',
                ],
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'O mnie',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lokalizacja',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
