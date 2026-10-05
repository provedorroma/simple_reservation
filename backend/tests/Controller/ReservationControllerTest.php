<?php

namespace App\Tests\Controller;

use App\Booking\Domain\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        static::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('DELETE FROM '.Reservation::class)
            ->execute();
    }

    public function testCreateAndShow(): void
    {
        $created = $this->request('POST', '/api/reservations', $this->payload());

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Jane Doe', $created['customerName']);
        self::assertSame('pending', $created['status']);
        self::assertResponseHeaderSame('Location', '/api/reservations/'.$created['id']);

        $shown = $this->request('GET', '/api/reservations/'.$created['id']);

        self::assertResponseIsSuccessful();
        self::assertSame($created['id'], $shown['id']);
    }

    public function testCreateRejectsInvalidPayload(): void
    {
        $this->request('POST', '/api/reservations', [
            'customerName' => '',
            'email' => 'not-an-email',
            'reservationDate' => '2000-01-01T19:00:00+00:00',
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testIndexListsReservations(): void
    {
        $this->request('POST', '/api/reservations', $this->payload());
        $this->request('POST', '/api/reservations', $this->payload(['customerName' => 'John Roe']));

        $list = $this->request('GET', '/api/reservations');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $list);
    }

    public function testUpdate(): void
    {
        $created = $this->request('POST', '/api/reservations', $this->payload());

        $updated = $this->request('PUT', '/api/reservations/'.$created['id'], $this->payload(['customerName' => 'Jane Smith']));

        self::assertResponseIsSuccessful();
        self::assertSame('Jane Smith', $updated['customerName']);
        self::assertSame('jane@example.com', $updated['email']);
    }

    public function testConfirmThenCancel(): void
    {
        $created = $this->request('POST', '/api/reservations', $this->payload());

        $confirmed = $this->request('POST', '/api/reservations/'.$created['id'].'/confirm');
        self::assertResponseIsSuccessful();
        self::assertSame('confirmed', $confirmed['status']);

        $this->request('POST', '/api/reservations/'.$created['id'].'/confirm');
        self::assertResponseStatusCodeSame(409);

        $cancelled = $this->request('POST', '/api/reservations/'.$created['id'].'/cancel');
        self::assertResponseIsSuccessful();
        self::assertSame('cancelled', $cancelled['status']);

        $this->request('PUT', '/api/reservations/'.$created['id'], $this->payload());
        self::assertResponseStatusCodeSame(409);
    }

    public function testShowUnknownReservationReturns404(): void
    {
        $this->request('GET', '/api/reservations/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'customerName' => 'Jane Doe',
            'email' => 'jane@example.com',
            'reservationDate' => (new \DateTimeImmutable('+7 days'))->format(\DATE_ATOM),
        ];
    }

    private function request(string $method, string $uri, ?array $body = null): mixed
    {
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: null === $body ? null : json_encode($body));

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
