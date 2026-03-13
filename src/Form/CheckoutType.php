<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
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
            ->add('sameAsBilling', CheckboxType::class, [
                'label' => 'Utiliser la même adresse pour la livraison',
                'mapped' => false,
                'required' => false,
                'data' => true,
            ])
            ->add('shippingAddress', CheckoutAddressType::class, [
                'label' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes de commande (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Instructions de livraison, message...'],
            ])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de naissance *',
                'mapped' => false,
                'required' => true,
                'data' => $options['birthDate'],
                'attr' => [
                    'max' => (new \DateTime('-18 years'))->format('Y-m-d'),
                    'min' => '1900-01-01',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez indiquer votre date de naissance.'),
                ],
            ])
            ->add('ageVerification', CheckboxType::class, [
                'label' => 'Je certifie avoir 18 ans ou plus et accepte de recevoir des produits alcoolisés.',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(message: 'Vous devez certifier être majeur(e) pour passer commande.'),
                ],
            ]);

        // Quand "même adresse" est coché, copier la facturation dans la livraison
        // avant la validation pour que les contraintes NotBlank passent
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (!empty($data['sameAsBilling'])) {
                $data['shippingAddress'] = $data['billingAddress'] ?? [];
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'is_guest' => true,
            'birthDate' => null,
        ]);

        $resolver->setAllowedTypes('is_guest', 'bool');
        $resolver->setAllowedTypes('birthDate', ['null', \DateTimeInterface::class]);
    }
}
