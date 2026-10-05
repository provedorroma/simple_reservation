<?php

namespace App\Booking\Domain\Exception;

use App\Booking\Domain\ReservationStatus;

final class InvalidStatusTransition extends BookingException
{
    public static function from(ReservationStatus $from, ReservationStatus $to): self
    {
        return new self(\sprintf('Cannot change a %s reservation to %s.', $from->value, $to->value));
    }
}
