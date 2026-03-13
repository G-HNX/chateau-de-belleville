<?php

declare(strict_types=1);

namespace App\Security;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

/**
 * Envoi du code d'authentification à deux facteurs par email.
 * Implémente l'interface de Scheb 2FA pour envoyer le code
 * via un template Twig dédié (email/2fa_code.html.twig).
 */
class TwoFactorAuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    /** Envoie le code 2FA par email à l'utilisateur concerné. */
    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();
        if ($authCode === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@chateau-belleville.fr', 'Château de Belleville'))
            ->to($user->getEmailAuthRecipient())
            ->subject('Votre code de vérification — Château de Belleville')
            ->htmlTemplate('email/2fa_code.html.twig')
            ->context([
                'authCode' => $authCode,
            ]);

        $this->mailer->send($email);
    }
}
