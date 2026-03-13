<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig fournissant le nonce CSP pour les scripts inline.
 * Le nonce est généré une seule fois par requête et partagé avec
 * le SecurityHeadersSubscriber via les attributs de la Request.
 */
class CspNonceExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->getCspNonce(...)),
        ];
    }

    /** Retourne le nonce CSP de la requête courante, en le créant si nécessaire. */
    public function getCspNonce(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $nonce = $request?->attributes->get('csp_nonce');
        if ($nonce === null) {
            $nonce = base64_encode(random_bytes(16));
            $request?->attributes->set('csp_nonce', $nonce);
        }

        return $nonce;
    }
}
