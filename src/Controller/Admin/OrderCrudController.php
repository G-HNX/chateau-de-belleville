<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order\Order;
use App\Enum\OrderStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['reference', 'customerEmail', 'customerLastName']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('reference', 'Référence')
            ->setFormTypeOption('disabled', true);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(array_combine(
                array_map(fn (OrderStatus $s) => $s->label(), OrderStatus::cases()),
                OrderStatus::cases(),
            ))
            ->renderAsBadges([
                OrderStatus::PENDING->value => 'warning',
                OrderStatus::PAID->value => 'info',
                OrderStatus::PROCESSING->value => 'primary',
                OrderStatus::SHIPPED->value => 'info',
                OrderStatus::DELIVERED->value => 'success',
                OrderStatus::CANCELLED->value => 'danger',
                OrderStatus::REFUNDED->value => 'secondary',
            ]);
        yield AssociationField::new('customer', 'Client')
            ->hideOnForm();
        yield EmailField::new('customerEmail', 'Email')
            ->onlyOnDetail();
        yield TextField::new('customerFirstName', 'Prénom')
            ->onlyOnDetail();
        yield TextField::new('customerLastName', 'Nom')
            ->onlyOnDetail();
        yield MoneyField::new('totalInCents', 'Total TTC')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setFormTypeOption('disabled', true);
        yield MoneyField::new('subtotalInCents', 'Sous-total HT')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->onlyOnDetail();
        yield MoneyField::new('taxAmountInCents', 'TVA')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->onlyOnDetail();
        yield MoneyField::new('shippingCostInCents', 'Livraison')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->onlyOnDetail();
        yield TextField::new('trackingNumber', 'N° suivi')
            ->hideOnIndex();
        yield TextField::new('carrier', 'Transporteur')
            ->hideOnIndex();
        yield TextareaField::new('adminNotes', 'Notes admin')
            ->hideOnIndex();
        yield TextareaField::new('customerNotes', 'Notes client')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);
        yield DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);
        yield DateTimeField::new('paidAt', 'Payée le')
            ->onlyOnDetail();
        yield DateTimeField::new('shippedAt', 'Expédiée le')
            ->onlyOnDetail();
    }
}
