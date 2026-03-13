<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * Limite le nombre de tentatives de saisie du code 2FA.
 * Empêche le bruteforce sur le code d'authentification à deux facteurs
 * en s'appuyant sur le rate limiter Symfony (IP du client).
 */
class TwoFactorRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $twoFactorCodeLimiter,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::ATTEMPT => 'onAttempt',
        ];
    }

    /** Vérifie le quota de tentatives à chaque essai de code 2FA. */
    public function onAttempt(TwoFactorAuthenticationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp() ?? 'unknown';
        $limiter = $this->twoFactorCodeLimiter->create($ip);

        if ($limiter->consume()->isAccepted() === false) {
            throw new CustomUserMessageAuthenticationException(
                'Trop de tentatives. Veuillez réessayer dans quelques minutes.'
            );
        }
    }
}
