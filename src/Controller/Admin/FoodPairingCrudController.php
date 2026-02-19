<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Catalog\FoodPairing;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FoodPairingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FoodPairing::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Accord mets-vins')
            ->setEntityLabelInPlural('Accords mets-vins')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();
    }
}
