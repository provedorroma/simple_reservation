<?php

namespace App\Reservation\Domain\Exception;

final class ReservationDateInThePast extends ReservationException
{
    public static function create(\DateTimeImmutable $date): self
    {
        return new self(\sprintf('Reservation date %s must be in the future.', $date->format(\DATE_ATOM)));
    }
}
