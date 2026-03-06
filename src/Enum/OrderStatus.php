<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'une commande.
 * Cycle de vie : PENDING -> PAID -> PROCESSING -> SHIPPED -> DELIVERED
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Retourne le libelle francais du statut.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payée',
            self::PROCESSING => 'En préparation',
            self::SHIPPED => 'Expédiée',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
            self::REFUNDED => 'Remboursée',
        };
    }

    /**
     * Retourne la classe CSS Tailwind pour le badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::PAID => 'bg-green-100 text-green-800',
            self::PROCESSING => 'bg-blue-100 text-blue-800',
            self::SHIPPED => 'bg-purple-100 text-purple-800',
            self::DELIVERED => 'bg-gray-100 text-gray-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
            self::REFUNDED => 'bg-orange-100 text-orange-800',
        };
    }

    /**
     * Verifie si la commande peut transitionner vers un nouveau statut.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::PAID, self::CANCELLED], true),
            self::PAID => in_array($newStatus, [self::PROCESSING, self::CANCELLED, self::REFUNDED], true),
            self::PROCESSING => in_array($newStatus, [self::SHIPPED, self::CANCELLED], true),
            self::SHIPPED => $newStatus === self::DELIVERED,
            self::DELIVERED => $newStatus === self::REFUNDED,
            self::CANCELLED, self::REFUNDED => false,
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
            array_map(fn (self $status) => $status->label(), self::cases()),
            array_map(fn (self $status) => $status->value, self::cases())
        );
    }
}
