<?php

namespace App\Booking\Domain\Exception;

final class InvalidCustomerName extends BookingException
{
    public static function because(string $value): self
    {
        return new self(\sprintf('"%s" is not a valid customer name (1 to 255 characters).', $value));
    }
}
