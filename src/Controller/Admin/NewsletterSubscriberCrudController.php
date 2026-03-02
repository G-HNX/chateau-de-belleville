<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\NewsletterSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NewsletterSubscriberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NewsletterSubscriber::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Abonné newsletter')
            ->setEntityLabelInPlural('Abonnés newsletter')
            ->setDefaultSort(['subscribedAt' => 'DESC'])
            ->setSearchFields(['email'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('email', 'Email');
        yield DateTimeField::new('subscribedAt', 'Inscrit le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
        yield TextField::new('unsubscribeToken', 'Token désinscription')
            ->hideOnIndex()
            ->hideOnForm();
    }
}
