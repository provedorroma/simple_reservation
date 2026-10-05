<?php

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Enum\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidStatusTransition;
use App\Reservation\Domain\Exception\ReservationDateInThePast;
use App\Reservation\Domain\Exception\ReservationIsCancelled;
use App\Reservation\Domain\ValueObject\CustomerName;
use App\Reservation\Domain\ValueObject\Email;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    private function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        private CustomerName $customerName,

        #[ORM\Embedded(columnPrefix: false)]
        private Email $email,

        #[ORM\Column]
        private \DateTimeImmutable $reservationDate,

        #[ORM\Column(length: 30, enumType: ReservationStatus::class)]
        private ReservationStatus $status,

        #[ORM\Column]
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public static function book(
        CustomerName $customerName,
        Email $email,
        \DateTimeImmutable $reservationDate,
        \DateTimeImmutable $now,
    ): self {
        self::assertInTheFuture($reservationDate, $now);

        return new self($customerName, $email, $reservationDate, ReservationStatus::Pending, $now);
    }

    public function changeDetails(
        CustomerName $customerName,
        Email $email,
        \DateTimeImmutable $reservationDate,
        \DateTimeImmutable $now,
    ): void {
        if (ReservationStatus::Cancelled === $this->status) {
            throw ReservationIsCancelled::cannotChange($this->id);
        }
        if ($reservationDate != $this->reservationDate) {
            self::assertInTheFuture($reservationDate, $now);
        }

        $this->customerName = $customerName;
        $this->email = $email;
        $this->reservationDate = $reservationDate;
    }

    public function confirm(): void
    {
        $this->transitionTo(ReservationStatus::Confirmed);
    }

    public function cancel(): void
    {
        $this->transitionTo(ReservationStatus::Cancelled);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function customerName(): CustomerName
    {
        return $this->customerName;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function reservationDate(): \DateTimeImmutable
    {
        return $this->reservationDate;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function transitionTo(ReservationStatus $to): void
    {
        if (!$this->status->canTransitionTo($to)) {
            throw InvalidStatusTransition::from($this->status, $to);
        }

        $this->status = $to;
    }

    private static function assertInTheFuture(\DateTimeImmutable $date, \DateTimeImmutable $now): void
    {
        if ($date <= $now) {
            throw ReservationDateInThePast::create($date);
        }
    }
}
