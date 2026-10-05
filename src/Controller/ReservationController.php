<?php

namespace App\Controller;

use App\Dto\ReservationInput;
use App\Entity\Reservation;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservations', name: 'api_reservation_', format: 'json')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->reservationService->list());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ReservationInput $input): JsonResponse
    {
        $reservation = $this->reservationService->create($input);

        return $this->json($reservation, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_reservation_show', ['id' => $reservation->getId()]),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Reservation $reservation): JsonResponse
    {
        return $this->json($reservation);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Reservation $reservation, #[MapRequestPayload] ReservationInput $input): JsonResponse
    {
        return $this->json($this->reservationService->update($reservation, $input));
    }

    #[Route('/{id}/confirm', name: 'confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirm(Reservation $reservation): JsonResponse
    {
        return $this->json($this->reservationService->confirm($reservation));
    }

    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Reservation $reservation): JsonResponse
    {
        return $this->json($this->reservationService->cancel($reservation));
    }
}
