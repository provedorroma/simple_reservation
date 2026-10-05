<?php

namespace App\Booking\Application\Command;

final readonly class UpdateReservation
{
    public function __construct(
        public int $id,
        public string $customerName,
        public string $email,
        public \DateTimeImmutable $reservationDate,
    ) {
    }
}
