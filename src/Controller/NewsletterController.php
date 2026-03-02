<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Entity\User\User;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/newsletter')]
class NewsletterController extends AbstractController
{
    #[Route('/inscription', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriberRepository $subscriberRepo,
        UserRepository $userRepo,
        ValidatorInterface $validator,
    ): Response {
        if (!$this->isCsrfTokenValid('newsletter_subscribe', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_home');
        }

        $email = trim((string) $request->request->get('email', ''));

        $violations = $validator->validate($email, [
            new NotBlank(message: 'Veuillez saisir votre adresse email.'),
            new Email(message: 'Adresse email invalide.'),
        ]);

        if (count($violations) > 0) {
            $this->addFlash('error', (string) $violations->get(0)->getMessage());
            return $this->redirectToRoute('app_home');
        }

        // Si l'email correspond à un utilisateur inscrit, on active son opt-in
        $user = $userRepo->findOneBy(['email' => $email]);
        if ($user instanceof User) {
            if (!$user->isNewsletterOptIn()) {
                $user->setNewsletterOptIn(true);
                $em->flush();
            }
            $this->addFlash('success', 'Votre inscription à la newsletter a bien été enregistrée.');
            return $this->redirectToRoute('app_home');
        }

        // Sinon, abonné anonyme
        if ($subscriberRepo->findOneBy(['email' => $email]) !== null) {
            $this->addFlash('success', 'Cette adresse est déjà inscrite à notre newsletter.');
            return $this->redirectToRoute('app_home');
        }

        $subscriber = (new NewsletterSubscriber())->setEmail($email);
        $em->persist($subscriber);
        $em->flush();

        $this->addFlash('success', 'Merci ! Vous êtes bien inscrit(e) à notre newsletter.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/desinscription/{token}', name: 'app_newsletter_unsubscribe', methods: ['GET'])]
    public function unsubscribe(
        string $token,
        EntityManagerInterface $em,
        NewsletterSubscriberRepository $subscriberRepo,
        UserRepository $userRepo,
    ): Response {
        // Cherche dans les abonnés anonymes
        $subscriber = $subscriberRepo->findOneBy(['unsubscribeToken' => $token]);
        if ($subscriber !== null) {
            $user = $userRepo->findByEmail($subscriber->getEmail());
            if ($user instanceof User) {
                $user->setNewsletterOptIn(false);
            }
            $em->remove($subscriber);
            $em->flush();
            return $this->render('newsletter/unsubscribe.html.twig', ['success' => true]);
        }

        return $this->render('newsletter/unsubscribe.html.twig', ['success' => false]);
    }
}
