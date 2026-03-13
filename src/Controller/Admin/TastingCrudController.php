<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking\Tasting;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TastingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Tasting::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Formule de dégustation')
            ->setEntityLabelInPlural('Formules de dégustation')
            ->setSearchFields(['name', 'description']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isActive');
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Informations');
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();

        yield FormField::addPanel('Paramètres');
        yield MoneyField::new('priceInCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);
        yield IntegerField::new('durationMinutes', 'Durée (min)');
        yield IntegerField::new('maxParticipants', 'Max participants');
        yield IntegerField::new('minParticipants', 'Min participants')
            ->hideOnIndex();
        yield BooleanField::new('isActive', 'Active');
    }
}
