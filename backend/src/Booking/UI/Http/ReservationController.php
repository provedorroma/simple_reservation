<?php

namespace App\Booking\UI\Http;

use App\Booking\Application\Command\CancelReservation;
use App\Booking\Application\Command\ConfirmReservation;
use App\Booking\Application\Command\CreateReservation;
use App\Booking\Application\Command\UpdateReservation;
use App\Booking\Application\CommandBus;
use App\Booking\Application\Query\GetReservation;
use App\Booking\Application\Query\ListReservations;
use App\Booking\Application\Query\ReservationView;
use App\Booking\Application\QueryBus;
use App\Booking\UI\Http\Request\ReservationInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservations', name: 'api_reservation_', format: 'json')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListReservations()));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ReservationInput $input): JsonResponse
    {
        $id = $this->commandBus->dispatch(new CreateReservation($input->customerName, $input->email, $input->reservationDate));

        return $this->json($this->view($id), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_reservation_show', ['id' => $id]),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->view($id));
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] ReservationInput $input): JsonResponse
    {
        $this->commandBus->dispatch(new UpdateReservation($id, $input->customerName, $input->email, $input->reservationDate));

        return $this->json($this->view($id));
    }

    #[Route('/{id}/confirm', name: 'confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirm(int $id): JsonResponse
    {
        $this->commandBus->dispatch(new ConfirmReservation($id));

        return $this->json($this->view($id));
    }

    #[Route('/{id}/cancel', name: 'cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(int $id): JsonResponse
    {
        $this->commandBus->dispatch(new CancelReservation($id));

        return $this->json($this->view($id));
    }

    private function view(int $id): ReservationView
    {
        return $this->queryBus->ask(new GetReservation($id));
    }
}
