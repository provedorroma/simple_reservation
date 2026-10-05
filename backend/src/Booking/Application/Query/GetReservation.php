<?php

namespace App\Booking\Application\Query;

final readonly class GetReservation
{
    public function __construct(
        public int $id,
    ) {
    }
}
