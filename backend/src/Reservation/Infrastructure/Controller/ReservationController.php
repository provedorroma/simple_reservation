<?php

namespace App\Reservation\Infrastructure\Controller;

use App\Reservation\Application\DTO\CreateReservationDTO;
use App\Reservation\Application\DTO\ReservationResponseDTO;
use App\Reservation\Application\DTO\UpdateReservationDTO;
use App\Reservation\Application\Service\ReservationService;
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
        return $this->json(array_map(
            ReservationResponseDTO::fromEntity(...),
            $this->reservationService->listReservations(),
        ));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateReservationDTO $dto): JsonResponse
    {
        $reservation = $this->reservationService->createReservation($dto);

        return $this->json(ReservationResponseDTO::fromEntity($reservation), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_reservation_show', ['id' => $reservation->id()]),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json(ReservationResponseDTO::fromEntity($this->reservationService->getReservation($id)));
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] UpdateReservationDTO $dto): JsonResponse
    {
        return $this->json(ReservationResponseDTO::fromEntity($this->reservationService->updateReservation($id, $dto)));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $this->reservationService->deleteReservation($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/confirm', name: 'confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirm(int $id): JsonResponse
    {
        return $this->json(ReservationResponseDTO::fromEntity($this->reservationService->confirmReservation($id)));
    }

    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(int $id): JsonResponse
    {
        return $this->json(ReservationResponseDTO::fromEntity($this->reservationService->cancelReservation($id)));
    }
}
