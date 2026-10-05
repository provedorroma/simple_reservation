<?php

namespace App\Tests\Reservation\Application\Service;

use App\Reservation\Application\DTO\CreateReservationDTO;
use App\Reservation\Application\Service\ReservationService;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Enum\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidStatusTransition;
use App\Reservation\Domain\Exception\ReservationNotFound;
use App\Reservation\Domain\Repository\ReservationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReservationServiceTest extends KernelTestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        static::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('DELETE FROM '.Reservation::class)
            ->execute();
        $this->service = static::getContainer()->get(ReservationService::class);
    }

    public function testCreatePersistsPendingReservation(): void
    {
        $id = $this->service->createReservation($this->dto())->id();

        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $stored = static::getContainer()->get(ReservationRepositoryInterface::class)->findById($id);

        self::assertNotNull($stored);
        self::assertSame('Jane Doe', $stored->customerName()->toString());
        self::assertSame(ReservationStatus::Pending, $stored->status());
    }

    public function testCancelledReservationCannotBeConfirmed(): void
    {
        $id = $this->service->createReservation($this->dto())->id();
        $this->service->cancelReservation($id);

        $this->expectException(InvalidStatusTransition::class);
        $this->service->confirmReservation($id);
    }

    public function testDeletedReservationIsGone(): void
    {
        $id = $this->service->createReservation($this->dto())->id();
        $this->service->deleteReservation($id);

        $this->expectException(ReservationNotFound::class);
        $this->service->getReservation($id);
    }

    private function dto(): CreateReservationDTO
    {
        return new CreateReservationDTO('Jane Doe', 'jane@example.com', new \DateTimeImmutable('+1 day'));
    }
}
