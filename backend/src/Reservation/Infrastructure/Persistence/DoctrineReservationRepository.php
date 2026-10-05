<?php

namespace App\Reservation\Infrastructure\Persistence;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Repository\ReservationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineReservationRepository implements ReservationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(int $id): ?Reservation
    {
        return $this->entityManager->find(Reservation::class, $id);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Reservation::class)
            ->findBy([], ['reservationDate' => 'ASC']);
    }

    public function save(Reservation $reservation): void
    {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
    }

    public function remove(Reservation $reservation): void
    {
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();
    }
}
