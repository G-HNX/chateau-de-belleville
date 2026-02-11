<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Customer\Review;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Avis')
            ->setEntityLabelInPlural('Avis clients')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'content', 'user.firstName', 'user.lastName']);
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
            ->add('isApproved')
            ->add('rating')
            ->add('wine');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('wine', 'Vin')
            ->setFormTypeOption('disabled', true);
        yield AssociationField::new('user', 'Client')
            ->setFormTypeOption('disabled', true);
        yield IntegerField::new('rating', 'Note')
            ->setFormTypeOption('disabled', true);
        yield TextField::new('title', 'Titre')
            ->setFormTypeOption('disabled', true);
        yield TextareaField::new('content', 'Contenu')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();
        yield BooleanField::new('isApproved', 'Approuvé');
        yield DateTimeField::new('createdAt', 'Date')
            ->setFormTypeOption('disabled', true);
    }
}
