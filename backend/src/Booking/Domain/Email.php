<?php

namespace App\Booking\Domain;

use App\Booking\Domain\Exception\InvalidEmail;

final readonly class Email
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);
        if (mb_strlen($value) > 255 || false === filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::because($value);
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
