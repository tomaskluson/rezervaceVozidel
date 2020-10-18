<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Datetime;

/**
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findOldBookingsBy(UserInterface $user, int $monthsBack = 12)
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :u')
            ->andWhere('b.reservation_date_from >= :date')
            ->andWhere('b.reservation_date_to <= :date_now')
            ->setParameters([
                'u' => $user,
                'date' => $this->monthsBack($monthsBack),
                'date_now' => new DateTime()
            ])
            ->join('b.car', 'c')
            ->orderBy('b.reservation_date_from','DESC');

    }

    public function findNewBookingsBy(UserInterface $user)
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.reservation_date_to >= :date')
            ->setParameters([
                'user' => $user,
                'date' => new DateTime()
            ])
            ->join('b.car', 'c')
            ->orderBy('b.reservation_date_from','DESC');
    }

    public function findAllNewBookings(int $monthsBack = 12)
    {
        return $this->createQueryBuilder('b')
            ->where('b.reservation_date_to >= :date')
            ->andWhere('b.reservation_date_from >= :date_from')
            ->setParameters([
                'date_from' => $this->monthsBack($monthsBack),
                'date' => new DateTime()
            ])
            ->join('b.car', 'c')
            ->join('b.user', 'u')
            ->orderBy('b.reservation_date_from','DESC');
    }

    //získá konflikty rezervace
    public function getConflicts($date_from, $date_to, $car)
    {
        $result = $this->createQueryBuilder('r')
            ->where('r.car = :car')
            ->andWhere('r.reservation_date_to >= :date_from')
            ->andWhere('r.reservation_date_from <= :date_to')
            ->setParameters([
                'car' => $car,
                'date_from' => $date_from,
                'date_to' => $date_to
            ])->getQuery()->getResult();

        return ($result);
    }

    public function findAllBookingsBy(UserInterface $user, int $monthsBack = 12)
    {
        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.reservation_date_from >= :date')
            ->setParameters([
                'user' => $user,
                'date' => $this->monthsBack($monthsBack)
            ])
            ->join('b.car', 'c') // potřebujeme na paginaci :)
            ->orderBy('b.reservation_date_from','DESC');
    }

    public function findAllLimit(int $monthsBack = 12)
    {
        return $this->createQueryBuilder('b')
            ->where('b.reservation_date_from >= :date')
            ->setParameter('date', $this->monthsBack($monthsBack))
            ->join('b.car', 'c') // potřebujeme na paginaci :)
            ->join('b.user', 'u') // - || -
            ->orderBy('b.reservation_date_from','DESC');
    }

    public function findAll()
    {
        return $this->findBy([], ['reservation_date_from' => 'ASC']);
    }

    // Vrátí dnes mínus X měsíců
    private function monthsBack(int $monthsBack) : DateTime
    {
        $date = new DateTime();
        if ($monthsBack > 0) {
            $date->sub(new \DateInterval("P" . $monthsBack . "M")); // X měsíců zpět
            return $date;
        }
        return $date;
    }
}
