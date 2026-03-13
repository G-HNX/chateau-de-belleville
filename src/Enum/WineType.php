<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Types de vins disponibles dans le catalogue.
 */
enum WineType: string
{
    case RED = 'red';
    case WHITE = 'white';
    case ROSE = 'rose';
    case SPARKLING = 'sparkling';
    case SWEET = 'sweet';

    /**
     * Retourne le libelle francais du type de vin.
     */
    public function label(): string
    {
        return match ($this) {
            self::RED => 'Rouge',
            self::WHITE => 'Blanc',
            self::ROSE => 'Rose',
            self::SPARKLING => 'Effervescent',
            self::SWEET => 'Moelleux',
        };
    }

    /**
     * Retourne la couleur CSS associee au type.
     */
    public function color(): string
    {
        return match ($this) {
            self::RED => '#722F37',
            self::WHITE => '#F5E6C8',
            self::ROSE => '#FFB7C5',
            self::SPARKLING => '#F7E7CE',
            self::SWEET => '#FFD700',
        };
    }

    /**
     * Retourne la classe CSS Tailwind pour le badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::RED => 'bg-red-100 text-red-800',
            self::WHITE => 'bg-amber-100 text-amber-800',
            self::ROSE => 'bg-pink-100 text-pink-800',
            self::SPARKLING => 'bg-yellow-100 text-yellow-800',
            self::SWEET => 'bg-orange-100 text-orange-800',
        };
    }

    /**
     * Retourne les choix pour les formulaires.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return array_combine(
            array_map(fn (self $type) => $type->label(), self::cases()),
            array_map(fn (self $type) => $type->value, self::cases())
        );
    }
}
