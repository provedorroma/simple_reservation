<?php

namespace App\Tests\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Enum\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidCustomerName;
use App\Reservation\Domain\Exception\InvalidEmail;
use App\Reservation\Domain\Exception\InvalidStatusTransition;
use App\Reservation\Domain\Exception\ReservationDateInThePast;
use App\Reservation\Domain\Exception\ReservationIsCancelled;
use App\Reservation\Domain\ValueObject\CustomerName;
use App\Reservation\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-01 12:00');
    }

    public function testBookedReservationIsPending(): void
    {
        $reservation = $this->book();

        self::assertSame(ReservationStatus::Pending, $reservation->status());
        self::assertSame('Jane Doe', $reservation->customerName()->toString());
        self::assertEquals($this->now, $reservation->createdAt());
    }

    public function testCannotBookInThePast(): void
    {
        $this->expectException(ReservationDateInThePast::class);

        $this->book($this->now->modify('-1 day'));
    }

    public function testPendingCanBeConfirmedThenCancelled(): void
    {
        $reservation = $this->book();

        $reservation->confirm();
        self::assertSame(ReservationStatus::Confirmed, $reservation->status());

        $reservation->cancel();
        self::assertSame(ReservationStatus::Cancelled, $reservation->status());
    }

    public function testConfirmedCannotBeConfirmedAgain(): void
    {
        $reservation = $this->book();
        $reservation->confirm();

        $this->expectException(InvalidStatusTransition::class);
        $reservation->confirm();
    }

    public function testCancelledCannotBeConfirmed(): void
    {
        $reservation = $this->book();
        $reservation->cancel();

        $this->expectException(InvalidStatusTransition::class);
        $reservation->confirm();
    }

    public function testCancelledCannotBeChanged(): void
    {
        $reservation = $this->book();
        $reservation->cancel();

        $this->expectException(ReservationIsCancelled::class);
        $reservation->changeDetails(CustomerName::fromString('John'), Email::fromString('john@example.com'), $this->now->modify('+2 days'), $this->now);
    }

    public function testDetailsCanChangeWithoutMovingAPastDate(): void
    {
        $reservation = $this->book();
        $later = $this->now->modify('+2 days');

        // The existing date is in the past by now, but it isn't being changed.
        $reservation->changeDetails(CustomerName::fromString('Jane Smith'), Email::fromString('jane@example.com'), $reservation->reservationDate(), $later);

        self::assertSame('Jane Smith', $reservation->customerName()->toString());
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::fromString('not-an-email');
    }

    public function testBlankNameIsRejected(): void
    {
        $this->expectException(InvalidCustomerName::class);

        CustomerName::fromString('   ');
    }

    private function book(?\DateTimeImmutable $date = null): Reservation
    {
        return Reservation::book(
            CustomerName::fromString('Jane Doe'),
            Email::fromString('jane@example.com'),
            $date ?? $this->now->modify('+1 day'),
            $this->now,
        );
    }
}
