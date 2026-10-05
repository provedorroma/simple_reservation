<?php

namespace App\Booking\Application;

interface QueryBus
{
    public function ask(object $query): mixed;
}
