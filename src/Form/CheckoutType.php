<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isGuest = $options['is_guest'];

        if ($isGuest) {
            $builder
                ->add('firstName', TextType::class, [
                    'label' => 'Prénom *',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Le prénom est obligatoire.'),
                    ],
                ])
                ->add('lastName', TextType::class, [
                    'label' => 'Nom *',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'Le nom est obligatoire.'),
                    ],
                ])
                ->add('email', EmailType::class, [
                    'label' => 'Email *',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(message: 'L\'email est obligatoire.'),
                        new Email(message: 'L\'email n\'est pas valide.'),
                    ],
                ])
                ->add('phone', TelType::class, [
                    'label' => 'Téléphone',
                    'mapped' => false,
                    'required' => false,
                ]);
        }

        $builder
            ->add('billingAddress', CheckoutAddressType::class, [
                'label' => false,
            ])
            ->add('shippingAddress', CheckoutAddressType::class, [
                'label' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes de commande (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Instructions de livraison, message...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'is_guest' => true,
        ]);

        $resolver->setAllowedTypes('is_guest', 'bool');
    }
}
