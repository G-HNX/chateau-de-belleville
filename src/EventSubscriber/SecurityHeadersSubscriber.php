<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injecte les headers HTTP de sécurité sur toutes les réponses HTML.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
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

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            // Stripe.js + ES module shims polyfill (utilise des data: URIs pour les modules)
            "script-src 'self' https://js.stripe.com 'unsafe-inline' data:",
            // Stripe iframes + Google Fonts
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
