<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Newsletter;
use App\Entity\NewsletterSubscriber;
use App\Entity\User\User;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsletterCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly NewsletterSubscriberRepository $subscriberRepo,
        private readonly UserRepository $userRepo,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Newsletter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Newsletter')
            ->setEntityLabelInPlural('Newsletters')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'subject'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendAction = Action::new('sendNewsletter', 'Envoyer', 'fa fa-paper-plane')
            ->linkToCrudAction('sendNewsletter')
            ->displayIf(fn (Newsletter $n) => $n->getSentAt() === null)
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, $sendAction)
            ->add(Crud::PAGE_DETAIL, $sendAction)
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $a) => $a->displayIf(fn (Newsletter $n) => $n->getSentAt() === null))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn (Action $a) => $a->displayIf(fn (Newsletter $n) => $n->getSentAt() === null));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield TextField::new('subject', 'Objet email');
        yield TextEditorField::new('content', 'Contenu')
            ->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
        yield DateTimeField::new('sentAt', 'Statut')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value
                ? '✅ Envoyée le ' . $value->format('d/m/Y à H:i')
                : '📝 Brouillon'
            );
    }

    public function sendNewsletter(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->getInt('entityId');
        /** @var Newsletter|null $newsletter */
        $newsletter = $this->em->find(Newsletter::class, $entityId);

        if (!$newsletter instanceof Newsletter) {
            $this->addFlash('warning', 'Newsletter introuvable.');
            return $this->redirectToList();
        }

        if ($newsletter->getSentAt() !== null) {
            $this->addFlash('warning', 'Cette newsletter a déjà été envoyée le ' . $newsletter->getSentAt()->format('d/m/Y à H:i') . '.');
            return $this->redirectToList();
        }

        // Collecte des destinataires anonymes (NewsletterSubscriber)
        $subscribers = $this->subscriberRepo->findAllEmails();

        // Collecte des emails utilisateurs opt-in
        $userEmails = $this->userRepo->findNewsletterOptInEmails();

        // Fusion et dédoublonnage
        $allRecipients = [];
        foreach ($subscribers as $sub) {
            $allRecipients[strtolower($sub['email'])] = [
                'email' => $sub['email'],
                'unsubscribeUrl' => $this->urlGenerator->generate(
                    'app_newsletter_unsubscribe',
                    ['token' => $sub['token']],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ];
        }
        foreach ($userEmails as $email) {
            $key = strtolower($email);
            if (!isset($allRecipients[$key])) {
                $allRecipients[$key] = [
                    'email' => $email,
                    'unsubscribeUrl' => $this->urlGenerator->generate(
                        'app_account_profile',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                ];
            }
        }

        $sentCount = 0;
        foreach ($allRecipients as $recipient) {
            $email = (new TemplatedEmail())
                ->from('chateaudebelleville@gmail.com')
                ->to($recipient['email'])
                ->subject($newsletter->getSubject())
                ->htmlTemplate('email/newsletter.html.twig')
                ->context([
                    'newsletter' => $newsletter,
                    'unsubscribeUrl' => $recipient['unsubscribeUrl'],
                ]);

            $this->mailer->send($email);
            ++$sentCount;
        }

        $newsletter->setSentAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->addFlash('success', sprintf('Newsletter envoyée à %d destinataire(s).', $sentCount));

        return $this->redirectToList();
    }

    private function redirectToList(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
