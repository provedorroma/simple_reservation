<?php

namespace App\Booking\Domain\Exception;

final class ReservationNotFound extends BookingException
{
    public static function withId(int $id): self
    {
        return new self(\sprintf('Reservation %d not found.', $id));
    }
}
