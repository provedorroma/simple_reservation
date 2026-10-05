<?php

namespace App\Booking\Domain\Exception;

final class InvalidEmail extends BookingException
{
    public static function because(string $value): self
    {
        return new self(\sprintf('"%s" is not a valid email.', $value));
    }
}
