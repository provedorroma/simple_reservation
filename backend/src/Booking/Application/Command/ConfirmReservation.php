<?php

namespace App\Booking\Application\Command;

final readonly class ConfirmReservation
{
    public function __construct(
        public int $id,
    ) {
    }
}
