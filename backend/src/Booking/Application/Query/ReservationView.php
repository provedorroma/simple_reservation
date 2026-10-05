<?php

namespace App\Booking\Application\Query;

use App\Booking\Domain\Reservation;

final readonly class ReservationView
{
    public function __construct(
        public int $id,
        public string $customerName,
        public string $email,
        public \DateTimeImmutable $reservationDate,
        public string $status,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromReservation(Reservation $reservation): self
    {
        return new self(
            $reservation->id(),
            $reservation->customerName()->toString(),
            $reservation->email()->toString(),
            $reservation->reservationDate(),
            $reservation->status()->value,
            $reservation->createdAt(),
        );
    }
}
