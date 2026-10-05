<?php

namespace App\Booking\Domain;

use App\Booking\Domain\Exception\ReservationNotFound;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    /**
     * @throws ReservationNotFound
     */
    public function get(int $id): Reservation;

    /**
     * @return list<Reservation>
     */
    public function all(): array;
}
