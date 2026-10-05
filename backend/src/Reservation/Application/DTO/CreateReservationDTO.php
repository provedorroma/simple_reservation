<?php

namespace App\Reservation\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateReservationDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $customerName = '',

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email = '',

        #[Assert\NotNull]
        #[Assert\GreaterThan('now')]
        public ?\DateTimeImmutable $reservationDate = null,
    ) {
    }
}
