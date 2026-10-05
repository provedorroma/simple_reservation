<?php

namespace App\Booking\Application\Command;

use App\Booking\Domain\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CancelReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $reservations,
    ) {
    }

    public function __invoke(CancelReservation $command): void
    {
        $reservation = $this->reservations->get($command->id);
        $reservation->cancel();

        $this->reservations->save($reservation);
    }
}
