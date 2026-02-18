<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Catalog\Wine;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Wine::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Vin')
            ->setEntityLabelInPlural('Vins')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'slug', 'appellation.name']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('category')
            ->add('isActive')
            ->add('isFeatured');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();
        yield IntegerField::new('vintage', 'Millésime');
        yield MoneyField::new('priceInCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);
        yield IntegerField::new('stock', 'Stock');
        yield AssociationField::new('category', 'Catégorie');
        yield AssociationField::new('appellation', 'Appellation');
        yield AssociationField::new('grapeVarieties', 'Cépages')
            ->hideOnIndex();
        yield NumberField::new('alcoholDegree', 'Degré')
            ->hideOnIndex();
        yield TextField::new('servingTemperature', 'Température')
            ->hideOnIndex();
        yield TextField::new('agingPotential', 'Garde')
            ->hideOnIndex();
        yield IntegerField::new('volumeCl', 'Volume (cl)')
            ->hideOnIndex();
        yield TextareaField::new('shortDescription', 'Description courte')
            ->hideOnIndex();
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();
        yield TextareaField::new('terroir', 'Terroir')
            ->hideOnIndex();
        yield BooleanField::new('isActive', 'Actif');
        yield BooleanField::new('isFeatured', 'Mis en avant');
        yield NumberField::new('averageRating', 'Note moyenne')
            ->setNumDecimals(1)
            ->hideOnForm();
    }
}
