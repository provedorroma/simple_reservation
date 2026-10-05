<?php

namespace App\Booking\Application\Command;

final readonly class CreateReservation
{
    public function __construct(
        public string $customerName,
        public string $email,
        public \DateTimeImmutable $reservationDate,
    ) {
    }
}
