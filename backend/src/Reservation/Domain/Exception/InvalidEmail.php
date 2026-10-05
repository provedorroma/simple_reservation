<?php

namespace App\Reservation\Domain\Exception;

final class InvalidEmail extends ReservationException
{
    public static function because(string $value): self
    {
        return new self(\sprintf('"%s" is not a valid email.', $value));
    }
}
