<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Domain\DomainPhoto;
use App\Enum\DomainSection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DomainPhotoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DomainPhoto::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo du domaine')
            ->setEntityLabelInPlural('Photos du domaine')
            ->setDefaultSort(['section' => 'ASC', 'position' => 'ASC'])
            ->setSearchFields(['caption']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('section')
            ->add('isActive');
    }

    public function configureFields(string $pageName): iterable
    {
        $sectionChoices = array_combine(
            array_map(fn (DomainSection $s) => $s->label(), DomainSection::cases()),
            DomainSection::cases(),
        );

        yield ChoiceField::new('section', 'Section')
            ->setChoices($sectionChoices);
        yield ImageField::new('filename', 'Photo')
            ->setUploadDir('public/uploads/domain')
            ->setBasePath('/uploads/domain')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(Crud::PAGE_NEW === $pageName);
        yield TextField::new('caption', 'Légende')
            ->hideOnIndex();
        yield IntegerField::new('position', 'Position')
            ->setHelp('0 = premier, 1 = deuxième…');
        yield BooleanField::new('isActive', 'Active');
    }
}
