<?php

namespace App\Booking\Domain;

use App\Booking\Domain\Exception\InvalidCustomerName;

final readonly class CustomerName
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if ('' === $value || mb_strlen($value) > 255) {
            throw InvalidCustomerName::because($value);
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
