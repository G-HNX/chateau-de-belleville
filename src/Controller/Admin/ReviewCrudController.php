<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Customer\Review;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ReviewCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

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
            ->setSearchFields(['title', 'content', 'user.firstName', 'user.lastName', 'wine.name']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approveReview', 'Approuver', 'fa fa-check')
            ->linkToUrl(fn (Review $r) => $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction('approveReview')
                ->setEntityId($r->getId())
                ->set('token', $this->csrfTokenManager->getToken('admin_review_action')->getValue())
                ->generateUrl())
            ->displayIf(fn (Review $r) => !$r->isApproved())
            ->setCssClass('text-success');

        $rejectAction = Action::new('rejectReview', 'Rejeter', 'fa fa-times')
            ->linkToUrl(fn (Review $r) => $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction('rejectReview')
                ->setEntityId($r->getId())
                ->set('token', $this->csrfTokenManager->getToken('admin_review_action')->getValue())
                ->generateUrl())
            ->displayIf(fn (Review $r) => $r->isApproved())
            ->setCssClass('text-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
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

    public function approveReview(AdminContext $context): Response
    {
        $request = $context->getRequest();
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_review_action', $request->query->get('token', '')))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        /** @var Review $review */
        $review = $context->getEntity()->getInstance();
        $review->setIsApproved(true);
        $this->em->flush();

        $this->addFlash('success', 'Avis approuvé et publié.');

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }

    public function rejectReview(AdminContext $context): Response
    {
        $request = $context->getRequest();
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_review_action', $request->query->get('token', '')))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
        }

        /** @var Review $review */
        $review = $context->getEntity()->getInstance();
        $review->setIsApproved(false);
        $this->em->flush();

        $this->addFlash('warning', 'Avis rejeté et masqué.');

        return $this->redirect($this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl());
    }
}
