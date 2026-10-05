<?php

namespace App\Tests\Booking\Application;

use App\Booking\Application\Command\CancelReservation;
use App\Booking\Application\Command\ConfirmReservation;
use App\Booking\Application\Command\CreateReservation;
use App\Booking\Application\CommandBus;
use App\Booking\Application\Query\GetReservation;
use App\Booking\Application\QueryBus;
use App\Booking\Domain\Exception\InvalidStatusTransition;
use App\Booking\Domain\Exception\ReservationNotFound;
use App\Booking\Domain\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReservationUseCasesTest extends KernelTestCase
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    protected function setUp(): void
    {
        self::bootKernel();
        static::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('DELETE FROM '.Reservation::class)
            ->execute();
        $this->commandBus = static::getContainer()->get(CommandBus::class);
        $this->queryBus = static::getContainer()->get(QueryBus::class);
    }

    public function testCreatePersistsPendingReservation(): void
    {
        $id = $this->commandBus->dispatch(new CreateReservation('Jane Doe', 'jane@example.com', new \DateTimeImmutable('+1 day')));

        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $view = $this->queryBus->ask(new GetReservation($id));

        self::assertSame('Jane Doe', $view->customerName);
        self::assertSame('pending', $view->status);
    }

    public function testCancelledReservationCannotBeConfirmed(): void
    {
        $id = $this->commandBus->dispatch(new CreateReservation('Jane Doe', 'jane@example.com', new \DateTimeImmutable('+1 day')));
        $this->commandBus->dispatch(new CancelReservation($id));

        $this->expectException(InvalidStatusTransition::class);
        $this->commandBus->dispatch(new ConfirmReservation($id));
    }

    public function testUnknownReservationIsNotFound(): void
    {
        $this->expectException(ReservationNotFound::class);

        $this->queryBus->ask(new GetReservation(999999));
    }
}
