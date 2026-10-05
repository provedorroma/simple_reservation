<?php

namespace App\Reservation\Domain\ValueObject;

use App\Reservation\Domain\Exception\InvalidEmail;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Email
{
    private function __construct(
        #[ORM\Column(name: 'email', length: 255)]
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
