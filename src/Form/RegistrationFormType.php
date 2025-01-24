<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('username', TextType::class, [
                'label' => 'Nazwa użytkownika',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('birthDate', BirthdayType::class, [
                'label' => 'Data urodzenia',
                'widget' => 'single_text',
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Płeć',
                'choices' => [
                    'Mężczyzna' => 'M',
                    'Kobieta' => 'F',
                    'Inne' => 'O',
                ],
            ])
            ->add('seekingGender', ChoiceType::class, [
                'label' => 'Szukam',
                'choices' => [
                    'Mężczyzny' => 'M',
                    'Kobiety' => 'F',
                    'Wszystkich' => 'A',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lokalizacja',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Akceptuję regulamin i politykę prywatności',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Musisz zaakceptować regulamin i politykę prywatności.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Hasło',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Proszę podać hasło',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Twoje hasło powinno mieć co najmniej {{ limit }} znaków',
                        'max' => 4096,
                    ]),
                ],
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
