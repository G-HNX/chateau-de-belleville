<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Catalog\WineImage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WineImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WineImage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Image')
            ->setEntityLabelInPlural('Images des vins')
            ->setDefaultSort(['wine' => 'ASC', 'position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('wine', 'Vin');
        yield ImageField::new('filename', 'Image')
            ->setUploadDir('public/uploads/wines')
            ->setBasePath('/uploads/wines')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(Crud::PAGE_NEW === $pageName);
        yield TextField::new('altText', 'Texte alternatif')
            ->hideOnIndex();
        yield IntegerField::new('position', 'Position');
        yield BooleanField::new('isMain', 'Image principale');
    }
}
