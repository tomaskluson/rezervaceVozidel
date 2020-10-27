<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReservationRepository::class)
 */
class Reservation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reservation_date_from;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reservation_date_to;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reservation_added;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @ORM\ManyToOne(targetEntity=Car::class, inversedBy="reservations")
     * @ORM\JoinColumn(name="car_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $car;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="reservations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservationDateFrom(): ?\DateTimeInterface
    {
        return $this->reservation_date_from;
    }

    public function setReservationDateFrom(?\DateTimeInterface $reservation_date_from): self
    {
        $this->reservation_date_from = $reservation_date_from;

        return $this;
    }

    public function getReservationDateTo(): ?\DateTimeInterface
    {
        return $this->reservation_date_to;
    }

    public function setReservationDateTo(?\DateTimeInterface $reservation_date_to): self
    {
        $this->reservation_date_to = $reservation_date_to;

        return $this;
    }

    public function getReservationAdded(): ?\DateTimeInterface
    {
        return $this->reservation_added;
    }

    public function setReservationAdded(?\DateTimeInterface $reservation_added): self
    {
        $this->reservation_added = $reservation_added;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): self
    {
        $this->car = $car;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
