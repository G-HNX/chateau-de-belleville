<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Catalog\Appellation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AppellationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Appellation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Appellation')
            ->setEntityLabelInPlural('Appellations');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();
        yield TextField::new('region', 'Région');
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();
    }
}
