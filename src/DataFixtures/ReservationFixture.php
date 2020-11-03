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
        $car = $this->getReference(CarFixture::CAR_CAR1);
        $car2 = $this->getReference(CarFixture::CAR_CAR2);
        $user1 = $this->getReference(UserFixture::USER_USER_REFERENCE);
        $user2 = $this->getReference(UserFixture::USER_ADMIN_REFERENCE);

        $date = new DateTime();
        $enddate = new DateTime();

        for ($i=1; $i<20; $i++)
        {
            $enddate->modify("+5 hours"); // konec rezervace bude o 5h později

            $date2 = clone $date;
            $enddate2 = clone $enddate;

            $res = new Reservation();
            if ($i%2)
            {
                $res->setCar($car);
                $res->setUser($user1);
            } else {
                $res->setCar($car2);
                $res->setUser($user2);
            }

            $res->setNote("Rezervace $i");
            $res->setReservationDateFrom($date2);
            $res->setReservationDateTo($enddate2);

            $date->modify("+5 hours"); // začátek další rezervace bude o 5h později

            $manager->persist($res);
        }

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
