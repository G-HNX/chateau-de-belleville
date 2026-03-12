<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

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
