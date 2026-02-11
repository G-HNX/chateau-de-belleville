<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom *',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *',
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire.'),
                    new Email(message: 'L\'email n\'est pas valide.'),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('sujet', ChoiceType::class, [
                'label' => 'Sujet *',
                'placeholder' => 'Sélectionnez un sujet',
                'choices' => [
                    'Question sur une commande' => 'commande',
                    'Réservation dégustation' => 'reservation',
                    'Information produit' => 'produit',
                    'Partenariat professionnel' => 'partenariat',
                    'Autre' => 'autre',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le sujet est obligatoire.'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message *',
                'attr' => ['placeholder' => 'Écrivez votre message ici...'],
                'constraints' => [
                    new NotBlank(message: 'Le message est obligatoire.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}