<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Interest;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterestsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('interests', EntityType::class, [
                'class' => Interest::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'group_by' => function(Interest $interest) {
                    return $interest->getCategory()->getName();
                },
                'choice_attr' => function(Interest $interest) {
                    return [
                        'class' => 'interest-item'
                    ];
                },
                'attr' => [
                    'class' => 'interests-grid'
                ],
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'interests_update'
        ]);
    }
}
