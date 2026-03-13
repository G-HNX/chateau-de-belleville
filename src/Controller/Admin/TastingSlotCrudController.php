<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking\TastingSlot;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class TastingSlotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TastingSlot::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Créneau')
            ->setEntityLabelInPlural('Créneaux de dégustation')
            ->setDefaultSort(['date' => 'ASC', 'startTime' => 'ASC'])
            ->setSearchFields(['tasting.name']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('tasting', 'Formule'))
            ->add('date')
            ->add('isAvailable');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('tasting', 'Formule');
        yield DateField::new('date', 'Date');
        yield TimeField::new('startTime', 'Heure de début');
        yield IntegerField::new('availableSpots', 'Places disponibles');
        yield IntegerField::new('remainingSpots', 'Places restantes')
            ->hideOnForm();
        yield BooleanField::new('isAvailable', 'Disponible');
    }
}
