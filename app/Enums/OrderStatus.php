<?php

namespace App\Enums;

enum OrderStatus: string
{
    case REQUESTED = 'Produto solicitado';
    case SEPARATING = 'Em separação';
    case SHIPPED = 'Enviado';
    case DELIVERED = 'Produto entregue';
    case CANCELLED = 'Pedido cancelado';
    case RETURNED = 'Devolvido (Arrependimento)';

    /**
     * Define as transições de estado permitidas (F4 + S2).
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return false;
        }

        return match ($this) {
            self::REQUESTED => in_array($target, [
                self::SEPARATING,
                self::SHIPPED,
                self::DELIVERED,
                self::CANCELLED,
            ], true),
            self::SEPARATING => in_array($target, [
                self::SHIPPED,
                self::DELIVERED,
                self::CANCELLED,
            ], true),
            self::SHIPPED => in_array($target, [
                self::DELIVERED,
            ], true),
            self::DELIVERED => in_array($target, [
                self::RETURNED,
            ], true),
            self::CANCELLED, self::RETURNED => false,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::REQUESTED,
            self::SEPARATING,
            self::SHIPPED,
        ], true);
    }

    public function label(): string
    {
        return $this->value;
    }
}
