<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts d'une reservation de degustation.
 */
enum ReservationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    /**
     * Retourne le libelle francais du statut.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmee',
            self::CANCELLED => 'Annulee',
            self::COMPLETED => 'Terminee',
            self::NO_SHOW => 'Absent',
        };
    }

    /**
     * Retourne la classe CSS Tailwind pour le badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::CONFIRMED => 'bg-green-100 text-green-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
            self::COMPLETED => 'bg-gray-100 text-gray-800',
            self::NO_SHOW => 'bg-orange-100 text-orange-800',
        };
    }

    /**
     * Verifie si la reservation est active.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    /**
     * Retourne les choix pour les formulaires.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return array_combine(
            array_map(fn (self $status) => $status->label(), self::cases()),
            array_map(fn (self $status) => $status->value, self::cases())
        );
    }
}
