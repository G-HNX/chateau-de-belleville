<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking\Reservation;
use App\Enum\ReservationStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['reference', 'email', 'lastName', 'firstName', 'phone']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('slot')
            ->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence')
            ->setFormTypeOption('disabled', true);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_combine(
                array_map(fn (ReservationStatus $s) => $s->label(), ReservationStatus::cases()),
                ReservationStatus::cases(),
            ))
            ->renderAsBadges([
                ReservationStatus::PENDING->value => 'warning',
                ReservationStatus::CONFIRMED->value => 'success',
                ReservationStatus::CANCELLED->value => 'danger',
                ReservationStatus::COMPLETED->value => 'info',
                ReservationStatus::NO_SHOW->value => 'secondary',
            ]);
        yield AssociationField::new('slot', 'Créneau')
            ->setFormTypeOption('disabled', true);
        yield TextField::new('firstName', 'Prénom')
            ->setFormTypeOption('disabled', true);
        yield TextField::new('lastName', 'Nom')
            ->setFormTypeOption('disabled', true);
        yield EmailField::new('email', 'Email')
            ->onlyOnDetail();
        yield TelephoneField::new('phone', 'Téléphone')
            ->onlyOnDetail();
        yield IntegerField::new('numberOfParticipants', 'Participants');
        yield TextareaField::new('message', 'Message client')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);
        yield TextareaField::new('adminNotes', 'Notes admin')
            ->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Date')
            ->setFormTypeOption('disabled', true);
    }
}
