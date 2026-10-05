<?php

namespace App\Booking\Application\Command;

final readonly class CancelReservation
{
    public function __construct(
        public int $id,
    ) {
    }
}
