<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Booking\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $minParticipants = $options['min_participants'];
        $maxParticipants = $options['max_participants'];

        $choices = [];
        for ($i = $minParticipants; $i <= $maxParticipants; $i++) {
            $label = $i . ' personne' . ($i > 1 ? 's' : '');
            $choices[$label] = $i;
        }

        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom *',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom *',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *',
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone *',
            ])
            ->add('numberOfParticipants', ChoiceType::class, [
                'label' => 'Nombre de participants *',
                'choices' => $choices,
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Allergies alimentaires, occasion spéciale, questions...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'min_participants' => 1,
            'max_participants' => 10,
        ]);

        $resolver->setAllowedTypes('min_participants', 'int');
        $resolver->setAllowedTypes('max_participants', 'int');
    }
}