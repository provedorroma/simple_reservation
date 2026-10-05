<?php

namespace App\Booking\UI\Http;

use App\Booking\Domain\Exception\BookingException;
use App\Booking\Domain\Exception\InvalidStatusTransition;
use App\Booking\Domain\Exception\ReservationIsCancelled;
use App\Booking\Domain\Exception\ReservationNotFound;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Translates domain exceptions into HTTP responses; the domain knows nothing about HTTP.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof BookingException) {
            return;
        }

        $status = match (true) {
            $exception instanceof ReservationNotFound => Response::HTTP_NOT_FOUND,
            $exception instanceof InvalidStatusTransition,
            $exception instanceof ReservationIsCancelled => Response::HTTP_CONFLICT,
            default => Response::HTTP_UNPROCESSABLE_ENTITY,
        };

        $event->setThrowable(new HttpException($status, $exception->getMessage(), $exception));
    }
}
