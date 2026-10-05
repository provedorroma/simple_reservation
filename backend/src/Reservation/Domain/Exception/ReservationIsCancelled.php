<?php

namespace App\Reservation\Domain\Exception;

final class ReservationIsCancelled extends ReservationException
{
    public static function cannotChange(?int $id): self
    {
        return new self(\sprintf('Reservation %d is cancelled and cannot be changed.', $id));
    }
}
