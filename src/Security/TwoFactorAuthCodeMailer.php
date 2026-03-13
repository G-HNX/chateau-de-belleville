<?php

declare(strict_types=1);

namespace App\Security;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class TwoFactorAuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

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
