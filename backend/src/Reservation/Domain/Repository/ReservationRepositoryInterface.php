<?php

namespace App\Reservation\Domain\Repository;

use App\Reservation\Domain\Entity\Reservation;

interface ReservationRepositoryInterface
{
    public function findById(int $id): ?Reservation;

    /**
     * @return list<Reservation>
     */
    public function findAll(): array;

    public function save(Reservation $reservation): void;

    public function remove(Reservation $reservation): void;
}
