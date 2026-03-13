<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Entity\User\User;
use App\Form\RegistrationType;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\User\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class SecurityController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        NewsletterSubscriberRepository $subscriberRepo,
        RateLimiterFactoryInterface $registrationLimiter,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $registrationLimiter->create($request->getClientIp() ?? '0.0.0.0');
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de tentatives d\'inscription. Veuillez patienter avant de réessayer.');

                return $this->redirectToRoute('app_register');
            }
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $em->persist($user);
            if ($user->isNewsletterOptIn()) {
                // Supprimer l'éventuel abonné anonyme existant pour éviter une violation de contrainte unique
                $existing = $subscriberRepo->findOneBy(['email' => $user->getEmail()]);
                if ($existing === null) {
                    $em->persist((new NewsletterSubscriber())->setEmail($user->getEmail()));
                }
            }
            $em->flush();

            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@chateau-belleville.fr', 'Château de Belleville'))
                    ->to((string) $user->getEmail())
                    ->subject('Confirmez votre adresse email')
                    ->htmlTemplate('security/verification_email.html.twig')
                    ->context(['user' => $user])
            );

            return $this->redirectToRoute('app_register_success');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/inscription-confirmee', name: 'app_register_success', methods: ['GET'])]
    public function registerSuccess(): Response
    {
        return $this->render('security/register_success.html.twig');
    }

    #[Route('/renvoyer-verification', name: 'app_resend_verification')]
    public function resendVerification(Request $request, RateLimiterFactoryInterface $resendVerificationLimiter): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            return $this->redirectToRoute('app_account_index');
        }

        $limiter = $resendVerificationLimiter->create($request->getClientIp() ?? '0.0.0.0');
        if (!$limiter->consume(1)->isAccepted()) {
            $this->addFlash('error', 'Trop de demandes. Veuillez patienter avant de réessayer.');
            return $this->redirectToRoute('app_home');
        }

        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@chateau-belleville.fr', 'Château de Belleville'))
                ->to((string) $user->getEmail())
                ->subject('Confirmez votre adresse email')
                ->htmlTemplate('security/verification_email.html.twig')
                ->context(['user' => $user])
        );

        $this->addFlash('success', 'Email de vérification renvoyé. Consultez votre boîte de réception.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/verifier-email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');
        if (null === $id) {
            $this->addFlash('error', 'Lien de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            $this->addFlash('error', 'Lien de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('success', 'Votre adresse email est déjà vérifiée. Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', 'Le lien de vérification est invalide ou a expiré. '.$exception->getReason());
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Votre adresse email a été vérifiée. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}
