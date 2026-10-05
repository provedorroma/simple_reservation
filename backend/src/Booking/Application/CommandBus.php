<?php

namespace App\Booking\Application;

interface CommandBus
{
    /**
     * Runs the command in a transaction and returns the handler's result, if any.
     */
    public function dispatch(object $command): mixed;
}
