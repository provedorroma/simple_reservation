<?php

namespace App\Booking\Domain\Exception;

final class ReservationIsCancelled extends BookingException
{
    public static function cannotChange(?int $id): self
    {
        return new self(\sprintf('Reservation %d is cancelled and cannot be changed.', $id));
    }
}
