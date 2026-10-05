<?php

namespace App\Booking\Application\Query;

use App\Booking\Domain\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListReservationsHandler
{
    public function __construct(
        private readonly ReservationRepository $reservations,
    ) {
    }

    /**
     * @return list<ReservationView>
     */
    public function __invoke(ListReservations $query): array
    {
        return array_map(ReservationView::fromReservation(...), $this->reservations->all());
    }
}
