<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order\Order;
use App\Enum\OrderStatus;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emailService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {}

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
            ->setSearchFields(['reference', 'customerEmail', 'customerLastName'])
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/detail', 'admin/order_detail.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $invoiceAction = Action::new('invoice', 'Facture PDF', 'fa fa-file-pdf')
            ->linkToUrl(fn (Order $order) => $this->generateUrl('admin_order_invoice', ['id' => $order->getId()]))
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayAsLink();

        $orderSlipAction = Action::new('orderSlip', 'Bon de commande', 'fa fa-clipboard-list')
            ->linkToUrl(fn (Order $order) => $this->generateUrl('admin_order_slip', ['id' => $order->getId()]))
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayAsLink();

        $markProcessing = Action::new('markProcessing', 'En préparation', 'fa fa-box')
            ->linkToCrudAction('markAsProcessing')
            ->displayIf(fn (Order $o) => $o->getStatus() === OrderStatus::PAID);

        $markShipped = Action::new('markShipped', 'Marquer expédiée', 'fa fa-truck')
            ->linkToCrudAction('markAsShipped')
            ->displayIf(fn (Order $o) => $o->getStatus() === OrderStatus::PROCESSING);

        $markDelivered = Action::new('markDelivered', 'Marquer livrée', 'fa fa-check-circle')
            ->linkToCrudAction('markAsDelivered')
            ->displayIf(fn (Order $o) => $o->getStatus() === OrderStatus::SHIPPED);

        $cancelOrder = Action::new('cancelOrder', 'Annuler', 'fa fa-times')
            ->linkToCrudAction('cancelOrder')
            ->displayIf(fn (Order $o) => $o->canBeCancelled())
            ->setCssClass('text-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $invoiceAction)
            ->add(Crud::PAGE_DETAIL, $orderSlipAction)
            ->add(Crud::PAGE_DETAIL, $markProcessing)
            ->add(Crud::PAGE_DETAIL, $markShipped)
            ->add(Crud::PAGE_DETAIL, $markDelivered)
            ->add(Crud::PAGE_DETAIL, $cancelOrder)
            ->add(Crud::PAGE_INDEX, $invoiceAction)
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
        yield TextField::new('customerFullName', 'Client')
            ->hideOnForm();
        yield EmailField::new('customerEmail', 'Email')
            ->onlyOnDetail();
        yield TextField::new('customerPhone', 'Téléphone')
            ->onlyOnDetail();
        yield DateField::new('customerBirthDate', 'Date de naissance (majorité)')
            ->onlyOnDetail()
            ->setFormat('dd/MM/yyyy');
        yield MoneyField::new('totalInCents', 'Total TTC')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setFormTypeOption('disabled', true);
        yield MoneyField::new('subtotalInCents', 'Sous-total')
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

    public function markAsProcessing(AdminContext $context): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $order->setStatus(OrderStatus::PROCESSING);
        $this->em->flush();

        $this->addFlash('success', sprintf('Commande %s marquée en préparation.', $order->getReference()));

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl());
    }

    public function markAsShipped(AdminContext $context): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $order->markAsShipped($order->getTrackingNumber(), $order->getCarrier());
        $this->em->flush();

        $this->emailService->sendOrderShipped($order);

        $this->addFlash('success', sprintf('Commande %s marquée expédiée. Email envoyé au client.', $order->getReference()));

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl());
    }

    public function markAsDelivered(AdminContext $context): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $order->markAsDelivered();
        $this->em->flush();

        $this->addFlash('success', sprintf('Commande %s marquée livrée.', $order->getReference()));

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl());
    }

    public function cancelOrder(AdminContext $context): Response
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $order->setStatus(OrderStatus::CANCELLED);
        $this->em->flush();

        $this->addFlash('warning', sprintf('Commande %s annulée.', $order->getReference()));

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($order->getId())
            ->generateUrl());
    }
}
