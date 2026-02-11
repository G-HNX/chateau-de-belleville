<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CheckoutAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom *',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom *',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Adresse *',
                'constraints' => [
                    new NotBlank(message: 'L\'adresse est obligatoire.'),
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal *',
                'constraints' => [
                    new NotBlank(message: 'Le code postal est obligatoire.'),
                    new Regex(
                        pattern: '/^[0-9]{5}$/',
                        message: 'Le code postal doit contenir 5 chiffres.',
                    ),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville *',
                'constraints' => [
                    new NotBlank(message: 'La ville est obligatoire.'),
                ],
            ])
            ->add('country', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
