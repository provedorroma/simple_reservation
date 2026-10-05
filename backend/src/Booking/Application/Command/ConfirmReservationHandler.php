<?php

namespace App\Booking\Application\Command;

use App\Booking\Domain\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ConfirmReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $reservations,
    ) {
    }

    public function __invoke(ConfirmReservation $command): void
    {
        $reservation = $this->reservations->get($command->id);
        $reservation->confirm();

        $this->reservations->save($reservation);
    }
}
