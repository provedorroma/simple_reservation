<?php

namespace App\Reservation\Application\DTO;

use App\Reservation\Domain\Entity\Reservation;

/**
 * What the API returns: decoupled from the entity, so the entity can change without breaking clients.
 */
final readonly class ReservationResponseDTO
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

    public static function fromEntity(Reservation $reservation): self
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
