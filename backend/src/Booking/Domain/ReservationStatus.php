<?php

namespace App\Booking\Domain;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending => \in_array($to, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => self::Cancelled === $to,
            self::Cancelled => false,
        };
    }
}
