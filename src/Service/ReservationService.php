<?php

namespace App\Service;

use App\Dto\ReservationInput;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReservationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    /**
     * @return Reservation[]
     */
    public function list(): array
    {
        return $this->reservationRepository->findBy([], ['reservationDate' => 'ASC']);
    }

    public function create(ReservationInput $input): Reservation
    {
        $reservation = new Reservation();
        $this->apply($reservation, $input);

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $reservation;
    }

    public function update(Reservation $reservation, ReservationInput $input): Reservation
    {
        $this->assertStatus($reservation, self::STATUS_PENDING, self::STATUS_CONFIRMED);
        $this->apply($reservation, $input);

        $this->entityManager->flush();

        return $reservation;
    }

    public function confirm(Reservation $reservation): Reservation
    {
        $this->assertStatus($reservation, self::STATUS_PENDING);
        $reservation->setStatus(self::STATUS_CONFIRMED);

        $this->entityManager->flush();

        return $reservation;
    }

    public function cancel(Reservation $reservation): Reservation
    {
        $this->assertStatus($reservation, self::STATUS_PENDING, self::STATUS_CONFIRMED);
        $reservation->setStatus(self::STATUS_CANCELLED);

        $this->entityManager->flush();

        return $reservation;
    }

    private function apply(Reservation $reservation, ReservationInput $input): void
    {
        $reservation
            ->setCustomerName($input->customerName)
            ->setEmail($input->email)
            ->setReservationDate($input->reservationDate);
    }

    private function assertStatus(Reservation $reservation, string ...$allowed): void
    {
        if (!\in_array($reservation->getStatus(), $allowed, true)) {
            throw new ConflictHttpException(\sprintf('Reservation %d is %s.', $reservation->getId(), $reservation->getStatus()));
        }
    }
}
