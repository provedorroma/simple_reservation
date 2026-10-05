<?php

namespace App\Reservation\Domain\ValueObject;

use App\Reservation\Domain\Exception\InvalidCustomerName;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class CustomerName
{
    private function __construct(
        #[ORM\Column(name: 'customer_name', length: 255)]
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
