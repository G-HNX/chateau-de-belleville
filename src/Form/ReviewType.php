<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Customer\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Votre note *',
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Résumez votre avis en quelques mots'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Votre avis (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Partagez votre expérience avec ce vin...',
                    'rows' => 4,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
