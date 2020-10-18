<?php

namespace App\DataFixtures;

use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTime;

class ReservationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // zde získáme objekty z předešlých fixtures
        $car = $this->getReference(CarFixture::CAR_CAR1_REFERENCE);
        $user1 = $this->getReference(UserFixture::USER_USER_REFERENCE);
        $user2 = $this->getReference(UserFixture::USER_ADMIN_REFERENCE);

        $date = new DateTime();
        $enddate = new DateTime();
        $enddate->modify("+5 hours"); // rezervace bude trvat 5h

        $res = new Reservation();
        $res->setCar($car);
        $res->setUser($user1);
        $res->setNote("První rezervace");
        $res->setReservationDateFrom($date);
        $res->setReservationDateTo($enddate);

        $res2 = new Reservation();
        $res2->setCar($car);
        $res2->setUser($user2);
        $res2->setNote("Druhá rezervace");
        $res2->setReservationDateFrom($date);
        $res2->setReservationDateTo($enddate);

        $manager->persist($res);
        $manager->persist($res2);

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            UserFixture::class,
            CarFixture::class
        );
    }
}
