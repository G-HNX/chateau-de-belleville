<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injecte les headers HTTP de sécurité sur toutes les réponses HTML.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        // Générer un nonce unique par requête pour les scripts inline
        $request = $this->requestStack->getCurrentRequest();
        $nonce = $request?->attributes->get('csp_nonce');
        if ($nonce === null) {
            $nonce = base64_encode(random_bytes(16));
            $request?->attributes->set('csp_nonce', $nonce);
        }

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            // Stripe.js + nonce pour JSON-LD et scripts inline légitimes
            "script-src 'self' https://js.stripe.com 'nonce-{$nonce}'",
            // Stripe iframes
            "frame-src https://js.stripe.com",
            "style-src 'self' https://fonts.googleapis.com 'unsafe-inline'",
            "font-src 'self' https://fonts.gstatic.com",
            // Stripe API + tuiles OpenStreetMap (Leaflet)
            "connect-src 'self' https://api.stripe.com https://*.tile.openstreetmap.org",
            // Images locales + tuiles OSM + icônes Leaflet (unpkg.com)
            "img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));
    }
}
