<?php

namespace App\Tests\Service;

use App\Dto\ReservationInput;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $reservation = $this->service->create($this->input());

        $stored = static::getContainer()->get(ReservationRepository::class)->find($reservation->getId());
        self::assertNotNull($stored);
        self::assertSame(ReservationService::STATUS_PENDING, $stored->getStatus());
        self::assertSame('Jane Doe', $stored->getCustomerName());
    }

    public function testCancelledReservationCannotBeConfirmed(): void
    {
        $reservation = $this->service->cancel($this->service->create($this->input()));

        $this->expectException(ConflictHttpException::class);
        $this->service->confirm($reservation);
    }

    private function input(): ReservationInput
    {
        return new ReservationInput('Jane Doe', 'jane@example.com', new \DateTimeImmutable('+1 day'));
    }
}
