<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Noyau de l'application Symfony.
 * Utilise le MicroKernelTrait pour une configuration simplifiée
 * (routes, services et bundles définis dans config/).
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
