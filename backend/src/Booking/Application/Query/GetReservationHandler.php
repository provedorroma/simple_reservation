<?php

namespace App\Booking\Application\Query;

use App\Booking\Domain\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetReservationHandler
{
    public function __construct(
        private readonly ReservationRepository $reservations,
    ) {
    }

    public function __invoke(GetReservation $query): ReservationView
    {
        return ReservationView::fromReservation($this->reservations->get($query->id));
    }
}
