<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Journalise les événements de sécurité importants.
 */
class SecurityAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp() ?? 'unknown';
        $email = $event->getRequest()->request->get('email', 'unknown');

        $maskedEmail = strlen($email) > 3 ? substr($email, 0, 3) . '***' : '***';
        $this->logger->warning('Échec de connexion.', [
            'ip' => $ip,
            'email' => $maskedEmail,
        ]);
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp() ?? 'unknown';
        $user = $event->getUser();

        $this->logger->info('Connexion réussie.', [
            'ip' => $ip,
            'user' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user,
        ]);
    }
}
