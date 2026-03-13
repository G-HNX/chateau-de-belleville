<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute des headers Cache-Control sur les pages publiques.
 * Les pages authentifiées restent privées (Symfony s'en charge via le firewall).
 */
class CacheHeadersSubscriber implements EventSubscriberInterface
{
    private const PUBLIC_CACHEABLE_ROUTES = [
        'app_home',
        'app_shop',
        'app_wine_show',
        'app_domain_excellence',
        'app_domain_nature',
        'app_domain_transmission',
        'app_tasting_list',
        'app_tasting_show',
        'app_news',
        'app_legal',
        'app_cgv',
        'app_privacy',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Ne pas toucher aux réponses déjà configurées ou non-200
        if ($response->getStatusCode() !== 200 || $response->headers->has('Cache-Control')) {
            return;
        }

        $route = $request->attributes->get('_route');

        if (\in_array($route, self::PUBLIC_CACHEABLE_ROUTES, true)) {
            $response->setPublic();
            $response->setMaxAge(300); // 5 min
            $response->setSharedMaxAge(600); // 10 min CDN/proxy
            $response->headers->set('Vary', 'Accept-Encoding');
        }
    }
}
