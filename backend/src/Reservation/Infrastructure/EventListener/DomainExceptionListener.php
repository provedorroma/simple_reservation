<?php

namespace App\Reservation\Infrastructure\EventListener;

use App\Reservation\Domain\Exception\InvalidStatusTransition;
use App\Reservation\Domain\Exception\ReservationException;
use App\Reservation\Domain\Exception\ReservationIsCancelled;
use App\Reservation\Domain\Exception\ReservationNotFound;
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
        if (!$exception instanceof ReservationException) {
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
