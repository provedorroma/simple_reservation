<?php

namespace App\Reservation\Domain\Exception;

use App\Reservation\Domain\Enum\ReservationStatus;

final class InvalidStatusTransition extends ReservationException
{
    public static function from(ReservationStatus $from, ReservationStatus $to): self
    {
        return new self(\sprintf('Cannot change a %s reservation to %s.', $from->value, $to->value));
    }
}
