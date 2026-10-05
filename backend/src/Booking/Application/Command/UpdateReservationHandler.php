<?php

namespace App\Booking\Application\Command;

use App\Booking\Domain\CustomerName;
use App\Booking\Domain\Email;
use App\Booking\Domain\ReservationRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $reservations,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(UpdateReservation $command): void
    {
        $reservation = $this->reservations->get($command->id);
        $reservation->changeDetails(
            CustomerName::fromString($command->customerName),
            Email::fromString($command->email),
            $command->reservationDate,
            $this->clock->now(),
        );

        $this->reservations->save($reservation);
    }
}
