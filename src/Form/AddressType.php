<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Customer\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Nom de l\'adresse',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : Maison, Bureau...'],
            ])
            ->add('firstName', TextType::class, ['label' => 'Prénom *'])
            ->add('lastName', TextType::class, ['label' => 'Nom *'])
            ->add('street', TextType::class, ['label' => 'Adresse *'])
            ->add('complement', TextType::class, [
                'label' => 'Complément',
                'required' => false,
                'attr' => ['placeholder' => 'Bâtiment, étage, interphone...'],
            ])
            ->add('postalCode', TextType::class, ['label' => 'Code postal *'])
            ->add('city', TextType::class, ['label' => 'Ville *'])
            ->add('country', HiddenType::class)
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('isDefaultShipping', CheckboxType::class, [
                'label' => 'Adresse de livraison par défaut',
                'required' => false,
            ])
            ->add('isDefaultBilling', CheckboxType::class, [
                'label' => 'Adresse de facturation par défaut',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
