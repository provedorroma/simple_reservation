<?php

namespace App\Reservation\Application\Service;

use App\Reservation\Application\DTO\CreateReservationDTO;
use App\Reservation\Application\DTO\UpdateReservationDTO;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Exception\ReservationNotFound;
use App\Reservation\Domain\Repository\ReservationRepositoryInterface;
use App\Reservation\Domain\ValueObject\CustomerName;
use App\Reservation\Domain\ValueObject\Email;
use Psr\Clock\ClockInterface;

/**
 * Coordinates the reservation use cases. Business rules live in the Reservation entity.
 */
class ReservationService
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservations,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return list<Reservation>
     */
    public function listReservations(): array
    {
        return $this->reservations->findAll();
    }

    public function getReservation(int $id): Reservation
    {
        return $this->reservations->findById($id) ?? throw ReservationNotFound::withId($id);
    }

    public function createReservation(CreateReservationDTO $dto): Reservation
    {
        $reservation = Reservation::book(
            CustomerName::fromString($dto->customerName),
            Email::fromString($dto->email),
            $dto->reservationDate,
            $this->clock->now(),
        );

        $this->reservations->save($reservation);

        return $reservation;
    }

    public function updateReservation(int $id, UpdateReservationDTO $dto): Reservation
    {
        $reservation = $this->getReservation($id);
        $reservation->changeDetails(
            CustomerName::fromString($dto->customerName),
            Email::fromString($dto->email),
            $dto->reservationDate,
            $this->clock->now(),
        );

        $this->reservations->save($reservation);

        return $reservation;
    }

    public function confirmReservation(int $id): Reservation
    {
        $reservation = $this->getReservation($id);
        $reservation->confirm();

        $this->reservations->save($reservation);

        return $reservation;
    }

    public function cancelReservation(int $id): Reservation
    {
        $reservation = $this->getReservation($id);
        $reservation->cancel();

        $this->reservations->save($reservation);

        return $reservation;
    }

    public function deleteReservation(int $id): void
    {
        $this->reservations->remove($this->getReservation($id));
    }
}
