<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Sections du domaine affichées sur la page « Le Domaine ».
 * Chaque cas correspond à un bloc éditorial avec texte et photos.
 */
enum DomainSection: string
{
    case HISTOIRE    = 'histoire';
    case TERROIR     = 'terroir';
    case NATURE      = 'nature';
    case NATURE_BAS  = 'nature_bas';
    case EXCELLENCE  = 'excellence';
    case TRANSMISSION = 'transmission';

    /** Retourne le libellé français affiché dans l'administration. */
    public function label(): string
    {
        return match ($this) {
            self::HISTOIRE     => 'Le Domaine — Notre Histoire',
            self::TERROIR      => 'Le Domaine — Notre Terroir',
            self::NATURE       => 'Nature — Introduction',
            self::NATURE_BAS   => 'Nature — Vignoble (bas de page)',
            self::EXCELLENCE   => 'Excellence — Introduction',
            self::TRANSMISSION => 'Transmission — Introduction',
        };
    }
}
