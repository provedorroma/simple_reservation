<?php

namespace App\Reservation\Domain\Exception;

final class ReservationNotFound extends ReservationException
{
    public static function withId(int $id): self
    {
        return new self(\sprintf('Reservation %d not found.', $id));
    }
}
