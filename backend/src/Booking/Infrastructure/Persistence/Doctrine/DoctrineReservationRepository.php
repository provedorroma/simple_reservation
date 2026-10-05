<?php

namespace App\Booking\Infrastructure\Persistence\Doctrine;

use App\Booking\Domain\Exception\ReservationNotFound;
use App\Booking\Domain\Reservation;
use App\Booking\Domain\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineReservationRepository implements ReservationRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Reservation $reservation): void
    {
        $this->entityManager->persist($reservation);
        // Flushing here assigns the database-generated id; the command bus's
        // doctrine_transaction middleware still commits once per command.
        $this->entityManager->flush();
    }

    public function get(int $id): Reservation
    {
        return $this->entityManager->find(Reservation::class, $id)
            ?? throw ReservationNotFound::withId($id);
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Reservation::class)
            ->findBy([], ['reservationDate' => 'ASC']);
    }
}
